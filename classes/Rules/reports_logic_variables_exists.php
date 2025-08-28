<?php

namespace Stanford\GoProd;

class reports_logic_variables_exists implements ValidationsImplementation
{
    private $project;

    private $notifications = [];
    public $extra = '';
    public $break = false;

    public $modalTableHeader = array("Report Name", "Report ID", "Missing Variable", "Edit");
    public $inconsistentVariables = [];

    public $dataDictionary = [];

    public function __constructor($project, $notifications)
    {
        $this->setProject($project);
        $this->setNotifications($notifications);
        $this->dataDictionary = \REDCap::getDataDictionary('array');;
        $this->setExtra();
    }
    public function setExtra(): void
    {
        $fqcn = static::class; // e.g. Stanford\\GoProd\\is_irb_exists
        $short = ($p = strrpos($fqcn, '\\')) !== false ? substr($fqcn, $p + 1) : $fqcn; // is_irb_exists
        $boxid = $short . '_comment';
        $this->extra = Validations::getCheckDetailsTextBox($boxid);
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
     * @return void
     */
    public function setProject(\Project $project): void
    {
        $this->project = $project;
    }

    /**
     * @return mixed
     */
    public function validate()
    {
        $var = array();
        $logic_fields = $this->ExtractReportsLogic();
        $logic_fields_array = Validations::ExtractVariables($logic_fields);
        $fields = \REDCap::getFieldNames();
        $fields = Validations::AddCheckBoxes($fields);//adding the extra Checkbox variables
        foreach ($logic_fields_array as $variable) {
            if (!in_array($variable[2], $fields)) {
                $link_path = APP_PATH_WEBROOT . 'DataExport/index.php?pid=' . $this->getProject()->project_id .
                    '&report_id=' . $variable[1] . '&addedit=1';
                $link_to_edit = '<a href=' . $link_path . ' target="_blank" ><img src=' . APP_PATH_IMAGES . 'pencil.png></a>';
                array_push($var, array($variable[0], $variable[1], '<strong style="color: red">[' . $variable[2] . ']</strong>', $link_to_edit));
            }
        }
        $this->inconsistentVariables = $var;
        if (!empty($this->inconsistentVariables)) {
            return false;
        }
        return true;
    }


    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        return array(
            'title' => $this->getNotifications()['REPORTS_LOGIC_TITLE'],
            'body' => $this->getNotifications()['REPORTS_LOGIC_BODY'],
            'type' => $this->getNotifications()['WARNING'],
            'modal' => $this->inconsistentVariables,
            'extra' => $this->extra,
            'modalHeader' => $this->modalTableHeader,
            'links' => array(),
        );
    }

    /**
     * @return array
     */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * @param array $notifications
     * @return void
     */
    public function setNotifications(array $notifications): void
    {
        $this->notifications = $notifications;
    }

    public function ExtractReportsLogic()
    {

        $var = array();
        $sql = "SELECT  title as title, report_id as reportid, advanced_logic as logic " .
            " FROM redcap_reports where advanced_logic IS NOT NULL and project_id=" . $this->getProject()->project_id;
        $result = db_query($sql);
        while ($query_res = db_fetch_assoc($result)) {
            array_push($var, array($query_res['title'], $query_res['reportid'], $query_res['logic']));
        }
        return $var;
    }
}
