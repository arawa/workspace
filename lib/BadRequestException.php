<?php

namespace OCA\Workspace;

use OCP\AppFramework\Http;

class BadRequestException extends StatusException {

    public function __construct($message)
    {
        parent::__construct($message);
    }

    public function getStatus() {
        return HTTP::STATUS_BAD_REQUEST;
    }
}