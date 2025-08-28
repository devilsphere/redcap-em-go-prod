<?php

    namespace Stanford\GoProd;

    class users_in_dag_check implements ValidationsImplementation
    {
        private $project;

        private $notifications = [];

        public $break = false;

        public $extra = '';

        public $modalHeader = array("Username not in a DAG", "");

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
            $pid = $this->getProject()->project_id;
            $usersNotInDag = self::CheckUsersinDAG();

            if ( !empty($usersNotInDag) ) {

                $this->inconsistentFields = $usersNotInDag;
                return false;
            }
            else {
                return true;
            }
        }

        public function getErrorMessage()
        {
            return array(
                'title' => $this->getNotifications()['USERS_IN_DAG_TITLE'],
                'body' => $this->getNotifications()['USERS_IN_DAG_BODY'],
                'type' => $this->getNotifications()['WARNING'],
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

        public function CheckUsersinDAG()
        {
            try {
                $pid = $this->getProject()->project_id;
                $var = array();
                $sql = "SELECT username FROM redcap_user_rights WHERE project_id = " . $pid . " AND group_id IS NULL AND project_id IN (SELECT DISTINCT project_ID FROM redcap_data_access_groups WHERE project_id = " . $pid . ")";
                // Execute the query
                $result = db_query($sql);

                // Debug: Log the query result object
                $link_path = APP_PATH_WEBROOT . 'index.php?route=DataAccessGroupsController:index&pid=' . $pid;
                $link1 = '<a href="' . $link_path . '" target="_blank">Edit DAG</a>';
                // Fetch the results
                while ($query_res = db_fetch_assoc($result)) {

                    $var[] = array($query_res['username'],$link1);  // Note the quotes around 'username'
                }

                return $var;
            }
            catch (Exception $e) {
// TODO:  Log the exception using REDCap's log function
                return [];
            }
        }
    }
