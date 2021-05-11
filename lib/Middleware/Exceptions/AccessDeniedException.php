<?php

declare(strict_types=1);

namespace OCA\Workspace\Middleware\Exceptions;

use OCP\AppFramework\Http;

class AccessDeniedException extends \Exception {
    public function __construct()
    {
        parent::__construct('This user doesn\'t belong to General Manager group', Http::STATUS_FORBIDDEN);
    }
}
