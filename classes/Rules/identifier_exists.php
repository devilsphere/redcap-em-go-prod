<?php

namespace Stanford\GoProd;

class identifier_exists implements ValidationsImplementation
{

    private $project;

    private $notifications = [];
    public $extra = '';
    public $break = false;

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
        //\REDCap::email('msherm12@jh.edu', 'redcap@jh.edu', 'dictionary', json_encode(\REDCap::getDataDictionary(self::getProject(),'array')));
        foreach (\REDCap::getDataDictionary($this->getProject()->project_id,'array') as $field_name => $field_attributes) {
            if ($field_attributes['identifier'] == "y") {
                return true;
            }
        }
        return false;
    }

    public function getErrorMessage()
    {
        return array(
            'title' => $this->getNotifications()['IDENTIFIERS_TITLE'],
            'body' => $this->getNotifications()['IDENTIFIERS_BODY'],
            'type' => $this->getNotifications()['WARNING'],
            'extra' => $this->extra,
            'links' => array(
                array(
                    'url' => APP_PATH_WEBROOT . 'index.php?pid=' . $this->getProject()->project_id . '&route=IdentifierCheckController:index',
                    'title' => $this->getNotifications()['EDIT']
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
}
