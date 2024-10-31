<?php

namespace OCA\Workspace\Exceptions;

abstract class AbstractNotification extends \Exception
{
    public function __construct(protected string $title = 'Error', string $message, int $code)
    {
        parent::__construct($message, $code);
    }

    public function getTitle(): string {
        return $this->title;
    }
}
