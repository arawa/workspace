<?php

declare(strict_types=1);

namespace OCA\Workspace\Controller\Exceptions;

use OCP\AppFramework\Http;

class CreateGroupFolderException extends \Exception {
    public function __construct()
    {
        parent::__construct('GroupFolder API to create a groupfolder doesn\'t respond.', Http::STATUS_INTERNAL_SERVER_ERROR);
    }
}