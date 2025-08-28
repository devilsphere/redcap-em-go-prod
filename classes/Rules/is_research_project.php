<?php

namespace Stanford\GoProd;

class is_research_project implements ValidationsImplementation
{

    private $project;

    private $notifications = [];
    public $extra = '';
    public $break=false;

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
        return $this->getProject()->project['purpose'] === "2" ? true : false;
    }

    public function getErrorMessage()
    {
        return array(
            'title' => $this->getNotifications()['RESEARCH_PROJECT_TITLE'],
            'body' => $this->getNotifications()['RESEARCH_PROJECT_BODY'],
            'type' => $this->getNotifications()['INFO'],
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
