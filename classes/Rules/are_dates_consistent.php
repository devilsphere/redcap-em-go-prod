<?php

namespace Stanford\GoProd;

class are_dates_consistent implements ValidationsImplementation
{
    private $project;

    private $notifications = [];

    public $break = false;
    public $dateConsistentHtml = '';
    public $modalTableHeader = array("Instrument", "Variable / Field Name", "Field Label", "Date Format", "Edit");
    public $inconsistentDates = [];
    public $extra = '';
    public $dataDictionary = [];

    private $dateTypes = array('date_ymd', 'date_mdy', 'date_dmy', 'datetime_dmy', 'datetime_mdy', 'datetime_ymd', 'datetime_seconds_mdy', 'datetime_seconds_ymd');

    public function __constructor($project, $notifications)
    {
        $this->setProject($project);
        $this->setNotifications($notifications);
        $this->dataDictionary = \REDCap::getDataDictionary('array');
        $this->setExtra();
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
    public function setdateConsistentHtml($pid): void
    {
        $this->dateConsistentHtml = $this->getDateAdjustmentHtml($pid);
    }
    public function setExtra(): void
    {
        $this->extra = Validations::getCheckDetailsTextBox('are_dates_consistent_user_comment');
    }
    /**
     * @return mixed
     */
    public function validate()
    {
        $array = array();
        $array = $this->getDateQuestions();
        $array = $this->FindDateConsistencyProblems($array);
        if (!empty($array)) {
            $this->inconsistentDates = $array;
            return false;
        }
        return true;
    }
    public function FindDateConsistencyProblems($array)
    {

        $pid = $this->getProject()->project_id;
        $FilteredOut= array();
        $flag = 0;
        $prevformat = $array[0][3];
        $pfcounts = [];
        //$uniqueKeys = array_unique($array[3]);


        foreach($array as $val)
        {
            if($val[3] != $prevformat)
            {
                $flag = 1;
            }
            $pfcounts[$val[3]] = $pfcounts[$val[3]] + 1;
            $prevformat = $val[3];
        }


        if ($flag == 1)
        {
            arsort($pfcounts); //sort array placing the most used format as the first element
            foreach ($array as $item1){
                $link_path=APP_PATH_WEBROOT.'Design/online_designer.php?pid='.$pid.'&page='.$item1[0].'&field='.$item1[1];
                $link_to_edit='<a href='.$link_path.' target="_blank" ><img src='.APP_PATH_IMAGES.'pencil.png></a>';
                $label=Validations::TextBreak($item1[1]);
                if($item1[3] != array_key_first($pfcounts))
                {
                    array_push($FilteredOut,Array($item1[0],$item1[1],$label,'<strong style="color: blue">'.$item1[3].'</strong>',$link_to_edit));
                }
                else
                {
                    array_push($FilteredOut,Array($item1[0],$item1[1],$label,'<strong>'.$item1[3].'</strong>',$link_to_edit));
                }
            }
        }

        //self::setExtra();
        self::setdateConsistentHtml($pid);
        return $FilteredOut;
        //return  array_map("unserialize", array_unique(array_map("serialize", $FilteredOut))); //return just the unique values found
    }

    public function getDateAdjustmentHtml($pid)
    {
        $html = '<table id="PrintDatesConsistentErrorstbl" class="table table-striped">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<td colspan="5" class="gp-info-content">';
        $html .= '<h4 class="gp-title-content">Review Date Formatting</h4>';
        $html .= '<div class="gp-title-content">';
        $html .= '<div>The list of project date fields is provided below and shows the mix of date formats.</div>';
        $html .= '<div>This has the potential to introduce data quality issues when a user doesn\'t notice the mix of date formats being used (e.g., user thinks they are entering May 4, but it actually goes in as April 4th).</div>';
        $html .= '<div><b>Selecting one of these options will set all fields to the selected format.</b></div>';
        $html .= '<div><strong>Note:</strong> There may be times when a mix is actually desired and intentional. More often than not, the best option is to use the same format throughout the project.</div>';
        $html .= '<div><strong>Note:</strong> Click the View button to see each individual field/date type</div>';
        $html .= '</div>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td colspan="5" class="gp-title-content">';
        $html .= '<button type="button" title="Change ALL date fields to MDY" aria-label="Set date format MDY" onclick="ChangeDateFormat(\'MDY\',\''.$pid.'\')" style="margin:0 6px; padding:6px 14px; border:1px solid #B6C8E5; border-radius:8px; background:#FFFFFF; color:#103B66; font-weight:600; font-size:13px; cursor:pointer;" onmouseover="this.style.backgroundColor=\'#F3F8FF\'" onmouseout="this.style.backgroundColor=\'#FFFFFF\'">MDY</button>';
        $html .= '<button type="button" title="Change ALL date fields to DMY" aria-label="Set date format DMY" onclick="ChangeDateFormat(\'DMY\',\''.$pid.'\')" style="margin:0 6px; padding:6px 14px; border:1px solid #B6C8E5; border-radius:8px; background:#FFFFFF; color:#103B66; font-weight:600; font-size:13px; cursor:pointer;" onmouseover="this.style.backgroundColor=\'#F3F8FF\'" onmouseout="this.style.backgroundColor=\'#FFFFFF\'">DMY</button>';
        $html .= '<button type="button" title="Change ALL date fields to YMD" aria-label="Set date format YMD" onclick="ChangeDateFormat(\'YMD\',\''.$pid.'\')" style="margin:0 6px; padding:6px 14px; border:1px solid #B6C8E5; border-radius:8px; background:#FFFFFF; color:#103B66; font-weight:600; font-size:13px; cursor:pointer;" onmouseover="this.style.backgroundColor=\'#F3F8FF\'" onmouseout="this.style.backgroundColor=\'#FFFFFF\'">YMD</button>';
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '</table>';
        return $html;
    }

    public function getDateQuestions()
    {
        $var = array();
        // Loop through each field and do something with each
        foreach ($this->dataDictionary as $field_name => $field_attributes) {
            // Do something with this field if it is a checkbox field
            if ($field_attributes['field_type'] == "text" and in_array($field_attributes['text_validation_type_or_show_slider_number'], $this->dateTypes)) {
                $FormName = $field_attributes['form_name'];
                $FieldName = $field_attributes['field_name'];
                $DateFormatLong = $field_attributes['text_validation_type_or_show_slider_number'];
                $DateFormatShort = substr($field_attributes['text_validation_type_or_show_slider_number'], -3);
                array_push($var, array($FormName, $FieldName, $DateFormatLong, $DateFormatShort));
            }
        }
        return $var;
    }

    /**
     * @return mixed
     */
    public function getErrorMessage()
    {
        return array(
            'title' =>  $this->getNotifications()['DATE_CONSISTENT_TITLE'],
            'body' => $this->dateConsistentHtml,
            'type' => $this->getNotifications()['WARNING'],
            'modal' => $this->inconsistentDates,
            'modalHeader' => $this->modalTableHeader,
            'extra' => $this->extra,
            'links' => array(                array(
                'url' => 'https://www.google.com',
                'title' => 'Read More'
            )),
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
}
