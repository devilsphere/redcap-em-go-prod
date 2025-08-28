<?php

    namespace Stanford\GoProd;

    class e_consent_check implements ValidationsImplementation
    {
        private $project;

        private $notifications = [];

        public $break = false;

        public $extra = '';

        public $modalHeader = array("Form name", "Display Name", "Event Name", "Status", "View Settings");

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

                $econsent_found = self::CheckEConsentForms();

                if ( !empty($econsent_found) ) {

                    $this->inconsistentFields = $econsent_found;
                    return false;
                }
                else {
                    return true;
                }
            }
            catch (Exception $e) {
// TODO: Log error
                return true;
            }
        }

        public function getErrorMessage()
        {
            return array(
                'title' => $this->getNotifications()['E_CONSENT_TITLE'],
                'body' => $this->getNotifications()['E_CONSENT_BODY'],
                'type' => $this->getNotifications()['DANGER'],
                'modal' => $this->inconsistentFields,
                'modalHeader' => $this->modalHeader,
                'extra' => $this->extra,
                'links' => array(
                    array(
                        'url' => 'https://mrprcbcw.hosts.jhmi.edu/redcap/surveys/?s=9RRALR3YCT',
                        'title' => 'REDCap e-Consent Documentation',
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

        public function CheckEConsentForms()
        {
            $econsentformresult = false;
            $pid = $this->getProject()->project_id;
            $sql = "select count(*) as Cnt from redcap_surveys where project_id = " . $pid . " and (pdf_auto_archive = 2 or pdf_auto_archive = 1)";
            $result = db_query($sql);
            if ( $result1 = db_fetch_assoc($result) ) {
                $econsentcount = $result1['Cnt'];
            }
            if ( $econsentcount > 0 ) {
                $var = array();

                $sql = "SELECT rs.form_name AS 'InstrumentName',
			re.descrip as 'EventName',
                    rs.title AS 'SurveyTitle',
                    CASE rs.pdf_auto_archive
                        WHEN 1 THEN 'Auto Archive Enabled'
                        WHEN 2 THEN 'E Consent Enabled'
                    END AS 'Status'
            FROM redcap_surveys rs left join redcap_events_metadata re on rs.pdf_econsent_firstname_event_id = re.event_id
            WHERE rs.project_id = " . $pid . " AND rs.pdf_auto_archive IN (1, 2)";

                $econsentformresult = db_query($sql);
                while ($query_res = db_fetch_assoc($econsentformresult)) {
                    $link_path1 = APP_PATH_WEBROOT . 'Surveys/edit_info.php?pid=' . $pid . '&view=showform&page=' . $query_res['InstrumentName'] . '&redirectDesigner=1';
                    $link_to_edit1 = '<a href=' . $link_path1 . ' target="_blank" ><img src=' . APP_PATH_IMAGES . 'pencil.png></a>';
                    array_push($var, array($query_res['InstrumentName'], $query_res['SurveyTitle'], $query_res['EventName'], $query_res['Status'], $link_to_edit1));
                }

            }
            return $var;
        }


    }
