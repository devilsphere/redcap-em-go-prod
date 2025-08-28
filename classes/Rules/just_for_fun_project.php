<?php

namespace Stanford\GoProd;


class just_for_fun_project implements ValidationsImplementation
{

    private $project;

    private $notifications = [];
    public $extra = '';
    public $break=false;

    public function __constructor($project, $notifications)
    {
        $this->break = true;
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
        return $this->getProject()->project['purpose'] === "0" ? false : true;
    }

    public function getErrorMessage()
    {
        return array(
            'title' => $this->getNotifications()['JUST_FOR_FUN_PROJECT_TITLE'],
            'body' => $this->getNotifications()['JUST_FOR_FUN_PROJECT_TITLE'],
            'type' => $this->getNotifications()['DANGER'],
            'extra' => $this->extra,
            'links' => array(
                array(
                    'url' => APP_PATH_WEBROOT.'ProjectSetup/index.php?pid='.$this->getProject()->project_id,
                    'title' => $this->getNotifications()['PROJECT_SETUP']
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
