<?php

	namespace Stanford\GoProd;

	class e_consent_form_check implements ValidationsImplementation
	{
        private $project;

        private $notifications = [];

        public $break = false;

        public $extra = '';

        public $modalHeader = array("Form Name","Display Name","Link");

        public $inconsistentFields = [];

        public function __constructor($project, $notifications)
        {
            $this->setProject($project);
            $this->setNotifications($notifications);
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
            try {
                $econsent_found = [];
                $econsent_found = self::checkFormsForConsentString();
                //\REDCap::email('msherm12@jh.edu', 'redcap@jh.edu', 'econsent_found', json_encode($econsent_found.count()));

                if (!empty($econsent_found)) {
                    $this->inconsistentFields = $econsent_found;
                   return false;
                } else {
                    return true;
                }
            } catch (Exception $e) {
                // TODO: Log error
                return true;
            }
        }

        public function getErrorMessage()
        {
            return array(
                'title' => $this->getNotifications()['E_CONSENT_FORM_TITLE'],
                'body' => $this->getNotifications()['E_CONSENT_FORM_BODY'],
                'type' => $this->getNotifications()['DANGER'],
                'modal' => $this->inconsistentFields,
                'modalHeader' => $this->modalHeader,
                'extra' => $this->extra,
                'links' => array(
                    array(
                        'url' => 'https://mrprcbcw.hosts.jhmi.edu/redcap/surveys/?s=9RRALR3YCT',
                        'title' => 'REDCap e-Consent Documentation'
                    )
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
        public function checkFormsForConsentString()
        {
            $pid = $this->getProject()->project_id;
            // Get all instrument names
            $instrument_names = \REDCap::getInstrumentNames();
            // Initialize an array to store matching instrument names
            $matching_instruments = [];
            //\REDCap::email('msherm12@jh.edu', 'redcap@jh.edu', 'instrument_names', json_encode($instrument_names));
            // Loop through each instrument name and label
            foreach ($instrument_names as $unique_name => $label) {
                // Check if the string "consent" is found in the instrument name
                if (stripos($label, 'consent') !== false) {
                    $link_path1 = APP_PATH_WEBROOT . 'Design/online_designer.php?pid=' . $pid . '&page=' . $unique_name ;
                    $link_to_edit1 = '<a href=' . $link_path1 . ' target="_blank" ><img src=' . APP_PATH_IMAGES . 'pencil.png></a>';
                    // Add the matching instrument to the array
                    //\REDCap::email('msherm12@jh.edu', 'redcap@jh.edu', 'unique_name', json_encode($unique_name));
                    $matching_instruments[] = array($unique_name, $label, $link_to_edit1);

                }
            }

            // Return the array of matching instruments
            return $matching_instruments;
        }
	}
