<?php

namespace OCA\Workspace\Exceptions\Notifications;

abstract class AbstractNotificationException extends \Exception
{
    
    public function __construct(
        private string $title,
        string $message,
        /**
        * @var integer from OCP\AppFramework\Http
        */
        int $code
    )
    {
        parent::__construct($message, $code);
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
