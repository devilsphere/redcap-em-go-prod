<?php

namespace Stanford\GoProd;

require_once('classes/ValidationsImplementation.php');
require_once('classes/Validations.php');
require_once "emLoggerTrait.php";

// loads all defined rules
foreach (glob("classes/Rules/*.php") as $filename) {
    require_once($filename);
}

class GoProd extends \ExternalModules\AbstractExternalModule
{

    use emLoggerTrait;

    const ALL_VALIDATIONS = 'ALL_VALIDATIONS';

    /**
     * @var \Project
     */
    private $project;


    /**
     * @var array
     */
    private $notifications = [];

    /**
     * @var \Stanford\GoProd\Validations;
     */
    private $validations;

    public function __construct()
    {
        parent::__construct();
        // Other code to run when object is instantiated

    }

    private function setEnabledRules()
    {
        $settings = $this->getSystemSettings();
        $rules = array();
        foreach ($settings as $name => $setting) {
            if (in_array($name, array('enabled', 'discoverable-in-project', 'user-activate-permission')) || !is_bool($setting['value'])) {
                continue;
            }
            $temp = "Stanford\GoProd\\$name";
            if ($setting['value'] && class_exists($temp)) {
                // init validation class name which is coming from config.json settings.
                /** @var \Stanford\GoProd\just_for_fun_project $obj */
                $obj = new $temp();
                // add prefix
                if (property_exists($obj, 'prefix')) {
                    $obj->prefix = $this->PREFIX;
                }
                $obj->__constructor($this->getProject(), $this->getNotifications());
                $rules[$name] = $obj;

            }
        }
        $this->getValidations()->setEnabledRules($rules);
    }

//    public function redcap_module_link_check_display($project_id, $link)
//    {
//        //limit the logging link to Super Users
//        if ($link['key'] = 'goProdAdmin') {
//            if ($this->isSuperUser()) {
//                return $link;
//            } else {
//                return null;
//            }
//        }
//        return $link;
//    }

    public function redcap_every_page_top(int $project_id)
    {
        if (PAGE == 'ProjectSetup/index.php') {


            if ((isset($_GET['pid']) && $_GET['pid'] != "")) {
                global $Proj;
                $this->setProject($Proj);
                $this->setValidations(new Validations($Proj));
                $this->setEnabledRules();
            }

            // final check before showing real button. in case URL was hardcoded.
            if (isset($_GET['to_prod_plugin']) and $_GET['to_prod_plugin'] === "1") {
                // allow superusers to skip final rules checks.
                $user = $this->framework->getUser();
                if (!$user->isSuperUser()) {
                    $result = $this->redcap_module_ajax(self::ALL_VALIDATIONS, [], $this->getProjectId(), '', '', '', '', '', '', '', '', '', '', '');
                    foreach ($result as $rule) {
                        if (is_array($rule) and strtolower($rule['type']) === 'danger') {
                            $query = $_GET;
                            // replace parameter(s)
                            $query['to_prod_plugin'] = '0';
                            // rebuild url
                            $query_result = http_build_query($query);
                            header('Location: ' . $_SERVER['PHP_SELF'] . '?' . $query_result);
                            $this->exitAfterHook();
                        }
                    }
                }
            }
            $this->includeFile('pages/project_setup.php');
        }
    }

    public function redcap_module_ajax($action, $payload, $project_id, $record, $instrument, $event_id, $repeat_instance,
                                       $survey_hash, $response_id, $survey_queue_hash, $page, $page_full, $user_id, $group_id)
    {
        if($action == 'datechange') {
            $response = $this->handleDateChange($payload);
            return json_encode(['message' => $response]);
        }
        if($action == 'saveusercomment') {
            $response = $this->saveUserComment($payload);
            return json_encode(['message' => $response]);
        }
        if ((isset($_GET['pid']) && $_GET['pid'] != "")) {
            global $Proj;
            $this->setProject($Proj);
            $this->setValidations(new Validations($Proj));
            $this->setEnabledRules();
        }

        $this->emDebug('Action: ' . $action);
        if ($action == self::ALL_VALIDATIONS) {
            $result = [];
            foreach ($this->getValidations()->getEnabledRules() as $name => $validation) {
                // IF RULE CLASS DID NOT IMPLEMENT ValidationsImplementation IGNORE IT
                if (!$validation instanceof ValidationsImplementation) {
                    continue;
                }
                if (!$validation->validate()) {
                    // if rule requires to break the loop and returns only results of this rule.
                    if ($validation->break) {
                        return array($name => $validation->getErrorMessage());
                    } else {
                        $result[$name] = $validation->getErrorMessage();
                    }
                } else {
                    $result[$name] = true;
                }
            }
            return $result;
        } elseif (array_key_exists($action, $this->getValidations()->getEnabledRules())) {
            $validation = $this->getValidations()->getEnabledRules()[$action];
            if (!$validation->validate()) {

                return array($action => $validation->getErrorMessage());
            } else {
                return array($action => true);
            }
        } else {
            throw new \Exception("Action  $action is not defined");
        }
    }
public function saveUserComment($payload)
    {
        $pskey = $payload['pskey'] ?? null;
        $psvalue = $payload['psvalue'] ?? null;
        $pspid = $this->getProjectId();
        if (!$pskey) {
            return "Project setting missing for '$pspid'.";
        }

        // Save the project setting
        $this->setProjectSetting($pskey, $psvalue, $pspid);

        return "Project setting '$pskey' has been updated for '$pspid'.";
    }
    /**
     * @param string $path
     */
    public function includeFile($path)
    {
        include_once $path;
    }

    /**
     * @return array
     */
    public function getNotifications(): array
    {
        if (!$this->notifications) {
            $this->setNotifications();
        }
        return $this->notifications;
    }

    /**
     * @param array $notifications
     */
    public function setNotifications(): void
    {
        $path = dirname(__DIR__) . '/' . $this->PREFIX . '_' . $this->VERSION . "/language/notifications.ini";
        $temp = parse_ini_file($path);
        $temp['YES'] = 'Yes';
        $temp['NO'] = 'No';
        $this->notifications = $temp;;
    }

    /**
     * @return Validations
     */
    public function getValidations(): Validations
    {
        return $this->validations;
    }

    /**
     * @param Validations $validations
     */
    public function setValidations(Validations $validations): void
    {
        $this->validations = $validations;
    }

    /**
     * @return \Project
     */
    public function getProject(): \Project
    {
        return $this->project;
    }

    /**
     * @param \Project $project
     */
    public function setProject(\Project $project): void
    {
        $this->project = $project;
    }
    private function handleDateChange($payload)
    {
        $fldtype = $payload['fldtype'] ?? null;
        $pid = $payload['fldnamelist'] ?? null;

        if (!$fldtype || !$pid) {
            throw new \Exception("Missing 'fldtype' or 'fldnamelist' in payload.");
        }

        $validTypes = ['YMD', 'MDY', 'DMY'];
        if (!in_array($fldtype, $validTypes)) {
            throw new \Exception("Invalid date format type: $fldtype");
        }

        $queryMap = [
            'YMD' => "update redcap_metadata set element_validation_type = replace(replace(element_validation_type, '_mdy', '_ymd'), '_dmy', '_ymd') where project_id = ? and right(element_validation_type, 4) IN ('_mdy', '_dmy')",
            'MDY' => "update redcap_metadata set element_validation_type = replace(replace(element_validation_type, '_ymd', '_mdy'), '_dmy', '_mdy') where project_id = ? and right(element_validation_type, 4) IN ('_ymd', '_dmy')",
            'DMY' => "update redcap_metadata set element_validation_type = replace(replace(element_validation_type, '_ymd', '_dmy'), '_mdy', '_dmy') where project_id = ? and right(element_validation_type, 4) IN ('_ymd', '_mdy')",
        ];

        $query = $queryMap[$fldtype];
        $this->query($query, [$pid]);

        return "All date fields have been changed to the $fldtype format.";
    }

    function getTierIcon(string $tier): string
    {
        $tierClass = match (strtolower($tier)) {
            'gold' => 'tier-badge tier-gold',
            'silver' => 'tier-badge tier-silver',
            'bronze' => 'tier-badge tier-bronze',
            default => 'tier-badge tier-none',
        };


        $label = ucfirst(strtolower($tier));


// Keep FA icon separate; style the wrapper, not the <i> icon.
        return sprintf(
            '<span class="%s" title="%s"><i class="fa-solid fa-medal" aria-hidden="true"></i><span class="tier-text">%s</span></span>',
            htmlspecialchars($tierClass, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        );
    }
    public function getClass($class, $parent, $pidlist, $pid) {
        if ($class === 2) {
            return '<span class="prjchild">Child of PID ' . htmlspecialchars($parent) . '</span>';
        }

        if ($class === 1 && $pidlist != $pid) {
            $pids = array_filter(array_map('trim', explode(',', $pidlist)));
            $pids = array_diff($pids, [$pid]); // Remove current PID

            $pids = array_values($pids); // reindex array
            $count = count($pids);

            if ($count <= 3) {
                $display = implode(', ', $pids);
            } else {
                $firstThree = array_slice($pids, 0, 3);
                $remaining = $count - 3;
                $fullList = implode(', ', $pids);
                $display = implode(', ', $firstThree) . " (+{$remaining} more)";
                $display = '<span title="' . htmlspecialchars($fullList) . '">' . $display . '</span>';
            }

            return '<span class="prjparent">Parent of PIDs: ' . $display . '</span>';
        }

        return '<span class="prjStandard">Standard Project</span>';
    }
    public function getPurpose($purp)
    {
        switch ($purp) {
            case '0':
                return '<strong style="color:red;">Practice / Just for fun</strong>';
            case '1':
                return '<strong style="color:red;">Other</strong>';
            case '2':
                return 'Research';
            case '3':
                return 'Quality Improvement';
            case '4':
                return 'Operational Support';
            default:
                return '<strong style="color:red;">Unknown Purpose</strong>';
        }
    }
    public function getSystemwideEnabledModules(){
        $resultset = [];
        $params = [];
        $qry = "select directory_prefix from redcap_external_modules where external_module_id in (select external_module_id from redcap_external_module_settings where `key` = 'enabled' and project_id is null and `value` = 'true')";
        $result = $this->query($qry, $params);

        while($row = $result->fetch_assoc()){
            $resultset[] = $row['directory_prefix'];
        }

        return $resultset;
    }
}


