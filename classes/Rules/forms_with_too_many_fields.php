<?php

namespace Stanford\GoProd;

use ExternalModules\ExternalModules;

class forms_with_too_many_fields implements ValidationsImplementation
{
    private $project;

    private $notifications = [];

    public $break = false;

    public $numberOfFields = [];
    public $extra = '';
    private $maxRecommended = 100;

    public $modalHeader = array("Instrument Name", "Number of Fields");

    public $prefix = '';

    public function __constructor($project, $notifications)
    {
        $this->setProject($project);
        $this->setNotifications($notifications);
        $this->maxRecommended = ExternalModules::getSystemSetting($this->prefix, 'forms_with_too_many_fields_max_recommended');
        $this->setExtra();
    }
    public function setExtra(): void
    {
        $fqcn = static::class; // e.g. Stanford\\GoProd\\is_irb_exists
        $short = ($p = strrpos($fqcn, '\\')) !== false ? substr($fqcn, $p + 1) : $fqcn; // is_irb_exists
        $boxid = $short . '_comment';
        $this->extra = Validations::getCheckDetailsTextBox($boxid);
    }

    public function getProject(): \Project
    {
        return $this->project;
    }

    public function setProject(\Project $project): void
    {
        $this->project = $project;
    }

    public function validate(): bool
    {
        $var = array();
        //Call the Data Dictionary
        //$dd_array = REDCap::getDataDictionary('array');
        $array = self::getNumberOfFieldsByForm();
        foreach ($array as $item) {
            if ($item['count'] > $this->maxRecommended) {

                array_push($var, $item);

            }

        }

        $this->numberOfFields = $var;
        if (!empty($this->numberOfFields)) {
            return false;
        }
        return true;
    }

    public function getErrorMessage()
    {
        return array(
            'title' => $this->getNotifications()['MAX_NUMBER_OF_RECORDS_TITLE'],
            'body' => $this->getNotifications()['MAX_NUMBER_OF_RECORDS_BODY'],
            'type' => $this->getNotifications()['WARNING'],
            'links' => array(),
            'extra' => $this->extra,
            'modal' => $this->numberOfFields,
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


    public static function getNumberOfFieldsByForm()
    {
        $var = array();

        // Print out the names of all instruments in the project
        $instrument_names = \REDCap::getInstrumentNames();
        foreach ($instrument_names as $unique_name => $label) {

            $count_of_fields = 0;
            // Loop through each field and do something with each
            foreach (\REDCap::getDataDictionary() as $field_name => $field_attributes) {
                // count the fields using the form name associated to each question in the array
                if ($field_attributes['form_name'] == $unique_name) {
                    $count_of_fields++;
                }
            }
            //create an array with the count of fields in a form (form as a label).
            array_push($var, array('name' => $label, 'count' => $count_of_fields));
        }
        return $var;
    }
}
