<?php

namespace Stanford\GoProd;

class check_branching_logic_simplified implements ValidationsImplementation
{
    private $project;

    private $notifications = [];

    public $break = false;

    public $dataDictionary = [];

    public $inconsistentFields = [];
    public $extra = '';
    public $modalHeader = array("Instrument", "Variable / Field Name", "Field Label", "Options/Choices", "Edit");

    public function __constructor($project, $notifications)
    {
        $this->setProject($project);
        $this->setNotifications($notifications);
        $this->dataDictionary = \REDCap::getDataDictionary('array');
        $this->setExtra();
    }
    public function setExtra(): void
    {
        $fqcn = static::class; // e.g. Stanford\\GoProd\\is_irb_exists
        $short = ($p = strrpos($fqcn, '\\')) !== false ? substr($fqcn, $p + 1) : $fqcn;
        $boxid = $short . '_comment';
        $this->extra = Validations::getCheckDetailsTextBox($boxid);
    }
    public function getProject(): \Project
    {
        return $this->project;
    }
    public function getDD(): \Project
    {
        return $this->dataDictionary;
    }
    public function setProject(\Project $project): void
    {
        $this->project = $project;
    }

    public function validate(): bool
    {
        $branching_fields = $this->getBranchingLogicFields();
        $var = array();
        foreach ($branching_fields as $row) {
            $logic = $row[2] ?? null;
            if (!is_string($logic) || trim($logic) === '') {
                continue;
            }
            //\REDCap::email('msherm12@jh.edu', 'redcap@jh.edu', 'logic for '. $row[1] , json_encode($logic));
            try {
                if (!\LogicTester::evaluateLogicSingleRecord($logic,1)) {
                    //$invalidRows[] = $row;
                    $label = Validations::TextBreak($row[1]);


                    $link_path = APP_PATH_WEBROOT . 'Design/online_designer.php?pid=' . $this->getProject()->project_id .
                        '&page=' . $row[0] . '&field=' . $row[1] . '&branching=1';
                    $link_to_edit = '<a href=' . $link_path . ' target="_blank" ><img src=' . APP_PATH_IMAGES . 'arrow_branch_side.png></a>';

                    array_push($var, array(\REDCap::getInstrumentNames($row[0]), $row[1], $label, '<strong style="color: red">[' . $row[2] . ']</strong>', $link_to_edit));

                }
            } catch (\Throwable $e) {
                array_push($var, array('Error in Logic Tester', $row[1], $row[0], '<strong style="color: red">[' . $row[2] . ']</strong>', ''));
            }
        }

        ////////////////////////////////////////////////////////////////////////////////////////////////

        $this->inconsistentFields = $var;
        if(!empty($this->inconsistentFields)){
            return false;
        }
        return true;
    }

    public function getErrorMessage()
    {
        return array(
            'title' => $this->getNotifications()['BRANCHING_LOGIC_SIMPLE_TITLE'],
            'body'  => $this->getNotifications()['BRANCHING_LOGIC_SIMPLE_BODY'],
            'type'  => $this->getNotifications()['DANGER'],
            'links' => array(),
            'modal' => $this->inconsistentFields,
            'extra' => $this->extra,
            'modalHeader' => $this->modalHeader
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
     */
    public function setNotifications(array $notifications): void
    {
        $this->notifications = $notifications;
    }

    /**
     * @param $DataDictionary
     * @return array
     *
     * Extract the Fields with branching logic
     */
    public static function getBranchingLogicFields()
    {
        $var = array();
        // Loop through each field and do something with each
        foreach (\REDCap::getDataDictionary('array') as $field_name => $field_attributes) {
            // Do something with this field if it is a checkbox field
            if (strlen(trim($field_attributes['branching_logic'])) > 0) {
                $FormName = $field_attributes['form_name'];
                $FieldName = $field_attributes['field_name'];
                $BranchingLogic = $field_attributes['branching_logic'];
                array_push($var, array($FormName, $FieldName, $BranchingLogic));
            }
        }
        return $var;
    }


}
