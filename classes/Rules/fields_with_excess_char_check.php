<?php

	namespace Stanford\GoProd;
    use ExternalModules\ExternalModules;
	class fields_with_excess_char_check implements ValidationsImplementation
	{

        private $project;

        private $notifications = [];

        public $break = false;
        public $maxChars = 26;
        public $extra = '';

        public $modalHeader = array("Characters", "Field Name", "Field Label", "Field Type", "View Settings");

        public $inconsistentFields = [];
        public $dataDictionary = [];
        public function __constructor($project, $notifications)
        {
            $this->setProject($project);
            $this->setNotifications($notifications);
            $this->setExtra();
            $this->dataDictionary = \REDCap::getDataDictionary('array');
            $this->maxChars = ExternalModules::getSystemSetting($this->prefix, 'max-char-per-field');
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

            $ExcessCharsFields = self::fieldNameWithMoreThanXChars();

            if ( !empty($ExcessCharsFields) ) {

                $this->inconsistentFields = $ExcessCharsFields;
                return false;
            }
            else {
                return true;
            }
        }

        public function getErrorMessage()
        {
            return array(
                'title' => $this->getNotifications()['FIELDS_W_EXCESS_CHARS_TITLE'],
                'body' => $this->getNotifications()['FIELDS_W_EXCESS_CHARS_BODY'],
                'type' => $this->getNotifications()['INFO'],
                'modal' => $this->inconsistentFields,
                'modalHeader' => $this->modalHeader,
                'extra' => $this->extra,
                'links' => array(
                ),
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

        public function fieldNameWithMoreThanXChars() {
            // Initialize an array to store field names with excess Characters.
            $excessCharsFields = [];
            $pid = $this->getProject()->project_id;
            // Iterate through each field in the DataDictionary.
            foreach ($this->dataDictionary as $field_attributes) {
                // Check if the key 'Variable / Field Name' exists in the field_attributes array.
                if (isset($field_attributes['field_name'])) {
                    // Extract the field name from the field_attributes.
                    $field_name = $field_attributes['field_name'];

                    if (strlen($field_name) > $this->maxChars) {

                        // Add the entire field name to the list of duplicate fields.
                        if (isset($field_attributes['field_name'])) {
                            $Fieldcharcount = strlen($field_attributes['field_name']);
                        } else {
                            // Handle the error, such as assigning a default value or logging an error message
                            $Fieldcharcount = 0; // For instance, setting it to 0 or some default value
                        }
                        $FieldName = $field_attributes['field_name'];
                        $Label = $field_attributes['field_label'];
                        $FieldType = $field_attributes['field_type'];
                        $link_path = APP_PATH_WEBROOT . 'Design/online_designer.php?pid=' . $pid . '&page=' . $field_attributes['form_name'] . '&field=' . $field_attributes['field_name'];
                        $link_to_edit = '<a href=' . $link_path . ' target="_blank" ><img src=' . APP_PATH_IMAGES .'pencil.png></a>';
                        $excessCharsFields[] = array($Fieldcharcount,$FieldName, $Label, $FieldType,$link_to_edit);
                    }

                }
            }

            // Return the array of field names with excess characters
            return $excessCharsFields;
        }
	}
