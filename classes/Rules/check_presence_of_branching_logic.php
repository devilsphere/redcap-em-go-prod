<?php

    namespace Stanford\GoProd;

    class check_presence_of_branching_logic implements ValidationsImplementation
    {
        private $project;
        private $notifications = [];
        public $break = false;
        public $dataDictionary = [];
        public $inconsistentFields = [];
        public $extra = '';
        public $modalHeader = array("Instrument", "Variable / Field Name", "Field Label", "Options/Choices", "Reason", "Edit");

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
            $short = ($p = strrpos($fqcn, '\\')) !== false ? substr($fqcn, $p + 1) : $fqcn; // is_irb_exists
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

            $var = array();
            // to ingnore smart variables
            $array_smart_variables = array("user-name",
                "user-fullname",
                "user-email",
                "user-dag-name",
                "user-dag-id",
                "user-dag-label",
                "user-role-id",
                "user-role-name",
                "user-role-label",
                "calendar-link",
                "calendar-url",
                "record-name",
                "record-dag-name",
                "record-dag-id",
                "record-dag-label",
                "is-form",
                "instrument-name",
                "instrument-label",
                "is-survey",
                "survey-queue-url",
                "event-id",
                "event-number",
                "event-name",
                "event-label",
                "previous-event-name",
                "previous-event-label",
                "next-event-name",
                "next-event-label",
                "first-event-name",
                "first-event-label",
                "last-event-name",
                "last-event-label",
                "arm-number",
                "arm-label",
                "previous-instance",
                "current-instance",
                "next-instance",
                "first-instance",
                "last-instance",
                "new-instance",
                "project-id",
                "redcap-base-url",
                "redcap-version",
                "redcap-version-url",
                "survey-base-url");
            $branching_fields = $this->getBranchingLogicFields();
            $BranchingLogicArray = Validations::ExtractVariables($branching_fields);
            $fields = \REDCap::getFieldNames();
            $fields = array_merge(Validations::AddCheckBoxes($fields), $array_smart_variables);//adding the extra Checkbox variables

            foreach ($BranchingLogicArray as $variable) {
                $varName = $variable[2];

                if ( preg_match('/:(value|label)$/', $varName) ) {// Strip known suffixes like :value, :label, :checked, :unchecked from the variable name for comparison purposes
                    $varName = preg_replace('/:(?:value|label|checked|unchecked)$/', '', $varName);
                }
                if ( !in_array($varName, $fields) ) {
                    $label = Validations::TextBreak($variable[1]);
                    $link_path = APP_PATH_WEBROOT . 'Design/online_designer.php?pid=' . $this->getProject()->project_id .
                        '&page=' . $variable[0] . '&field=' . $variable[1] . '&branching=1';
                    $link_to_edit = '<a href=' . $link_path . ' target="_blank" ><img src=' . APP_PATH_IMAGES . 'arrow_branch_side.png></a>';
                    array_push($var, array(\REDCap::getInstrumentNames($variable[0]), $variable[1], $label, '<strong style="color: red">[' . $variable[2] . ']</strong>', 'Field Mismatch', $link_to_edit));
                }
            }

            $comparisonrows = self::extractEqualComparisons($branching_fields, true);
            $RadioDropDownFields = self::getRadioDropDownFields();
            $FailedComparisons = self::findNonMatchingComparisonRows($comparisonrows, $RadioDropDownFields);
            foreach ($FailedComparisons as $failedComparison) {
                $label = Validations::TextBreak($failedComparison[1]);
                $link_path = APP_PATH_WEBROOT . 'Design/online_designer.php?pid=' . $this->getProject()->project_id .
                    '&page=' . $failedComparison[0] . '&field=' . $failedComparison[1] . '&branching=1';
                $link_to_edit = '<a href=' . $link_path . ' target="_blank" ><img src=' . APP_PATH_IMAGES . 'arrow_branch_side.png></a>';
                array_push($var, array(\REDCap::getInstrumentNames($failedComparison[0]), $failedComparison[1], $label, '<strong style="color: red">[' . $failedComparison[3] . ']</strong>', 'Possible Value Mismatch', $link_to_edit));
            }

            $this->inconsistentFields = $var;
            if ( !empty($this->inconsistentFields) ) {
                return false;
            }
            return true;
        }

        public function getErrorMessage()
        {
            return array(
                'title' => $this->getNotifications()['BRANCHING_LOGIC_TITLE'],
                'body' => $this->getNotifications()['BRANCHING_LOGIC_BODY'],
                'type' => $this->getNotifications()['DANGER'],
                'links' => array(),
                'modal' => $this->inconsistentFields,
                'extra' => $this->extra,
                'modalHeader' => $this->modalHeader
            );
        }

        public function getNotifications(): array
        {
            return $this->notifications;
        }

        public function setNotifications(array $notifications): void
        {
            $this->notifications = $notifications;
        }

        public static function getBranchingLogicFields()
        {
            $var = array();
            // Loop through each field and do something with each
            foreach (\REDCap::getDataDictionary('array') as $field_name => $field_attributes) {
                // Do something with this field if it is a checkbox field
                if ( strlen(trim($field_attributes['branching_logic'])) > 0 ) {
                    $FormName = $field_attributes['form_name'];
                    $FieldName = $field_attributes['field_name'];
                    $BranchingLogic = $field_attributes['branching_logic'];
                    array_push($var, array($FormName, $FieldName, $BranchingLogic));
                }
            }
            return $var;
        }

        public static function getRadioDropDownFields()
        {
            $var = array();
            // Loop through each field and do something with each
            foreach (\REDCap::getDataDictionary('array') as $field_name => $field_attributes) {
                // Do something with this field if it is a checkbox field
                if ( $field_attributes['field_type'] == 'radio' || $field_attributes['field_type'] == 'dropdown' ) {
                    $FormName = $field_attributes['form_name'];
                    $FieldName = $field_attributes['field_name'];
                    $Choices = $field_attributes['select_choices_or_calculations'];
                    $ParsedChoices = self::extract_values_csv($Choices);
                    array_push($var, array($FormName, $FieldName, $ParsedChoices));
                }
            }
            return $var;
        }

        public static function extract_values_csv(string $input, string $pairDelimiter = '|', string $kvDelimiter = ','): string
        {
            if ( $input === '' ) {
                return '';
            }

            $pairs = explode($pairDelimiter, $input);
            $values = [];
            foreach ($pairs as $pair) {
                $pair = trim($pair);
                if ( $pair === '' ) {
                    continue; // skip empty segments
                }
                $parts = explode($kvDelimiter, $pair, 2);
                $value = trim($parts[0]);
                if ( $value === '' ) {
                    continue; // skip if value is empty after trimming
                }
                $values[] = $value;
            }
            return implode(',', $values);
        }

        public function extractEqualComparisons(array $rows, bool $dedupe = true): array
        {
            $results = [];
            $seen = [];


// Matches: one or more [token] groups immediately followed by a bare '=' and a value.
// RHS supports 'single', "double" quotes, or an unquoted token until whitespace or ')'.
            $pattern = '~((?:\[[^\]]+\]\s*)+)\s*=\s*(?:\'([^\']*)\'|"([^"]*)"|([^\s)]+))~u';


            foreach ($rows as $row) {
                if ( !isset($row[2]) || !is_string($row[2]) ) {
                    continue;
                }
                $cond = $row[2];


                if ( preg_match_all($pattern, $cond, $matches, PREG_SET_ORDER) ) {
                    foreach ($matches as $m) {
                        $lhs = $m[1];
// Determine RHS from the first non-empty capturing group among (single, double, unquoted)
                        $rhs = $m[2] !== '' ? $m[2] : ($m[3] !== '' ? $m[3] : $m[4]);


// Extract the last [token] from the LHS (handles [event][field])
                        if ( preg_match_all('~\[([^\]]+)\]~u', $lhs, $bTok) && !empty($bTok[1]) ) {
                            $field = (string)end($bTok[1]);
// Normalize: strip trailing :value and trim
                            $field = trim(preg_replace('~:value$~u', '', $field));
                            $value = (string)$rhs; // already unquoted by the regex branch


                            if ( $dedupe ) {
                                $key = $field . "\0" . $value;
                                if ( isset($seen[$key]) ) {
                                    continue;
                                }
                                $seen[$key] = true;
                            }


                            $results[] = array($row[0], $row[1], $field, $value);
                        }
                    }
                }
            }


            return $results;
        }

        /**
         * Build a lookup of Radio/Dropdown fields ⇒ allowed codes (strings).
         * Rows are shaped like: [instrument, field, "csv,codes,..."].
         * We merge duplicates across instruments.
         */
        function buildRadioDropdownIndex(array $radioDropdownFields): array
        {
            $idx = [];
            foreach ($radioDropdownFields as $r) {
                if ( !isset($r[1], $r[2]) ) continue;
                $field = (string)$r[1];
                $vals = array_filter(array_map('trim', explode(',', (string)$r[2])), static fn($v) => $v !== '');
                $idx[$field] = $idx[$field] ?? [];
                foreach ($vals as $v) {
                    $idx[$field][(string)$v] = true;
                }
            }
            return $idx; // field => associative set of codes
        }

        function findNonMatchingComparisonRows(
            array $comparisonRows,
            array $radioDropdownFields,
            bool  $looseNumericMatch = true,
            bool  $includeUnknownFields = false
        ): array
        {
            $index = self::buildRadioDropdownIndex($radioDropdownFields);
            $out = [];
            foreach ($comparisonRows as $row) {
                if ( !is_array($row) || !isset($row[2], $row[3]) ) continue;
                $field = (string)$row[2];
                $value = (string)$row[3];
                if ( !isset($index[$field]) ) {
                    if ( $includeUnknownFields ) {
                        $out[] = [$field, $value];
                    }
                    continue; // ignore unknown fields by default
                }
                $allowed = $index[$field];
                if ( isset($allowed[$value]) ) {
                    continue;
                }
                $matched = false;
                if ( $looseNumericMatch && is_numeric($value) ) {
                    foreach ($allowed as $code => $_) {
                        if ( is_numeric($code) && (string)(+$code) === (string)(+$value) ) {
                            $matched = true;
                            break; // numeric-equal; treat as match
                        }
                    }
                }
                if ( !$matched ) {
                    $out[] = array($row[0], $row[1], $field, $value);
                }
            }
            return $out;
        }

    }   ///////////////////end class
