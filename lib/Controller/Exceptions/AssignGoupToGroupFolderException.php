<?php

declare(strict_types=1);

namespace OCA\Workspace\Controller\Exceptions;

use OCP\AppFramework\Http;

class AssignGroupToGroupFolderException extends \Exception {
    public function __construct($groupname)
    {
        parent::__construct('GroupFolder API to assign the '. $groupname .' group doesn\'t work.', Http::STATUS_INTERNAL_SERVER_ERROR);
    }
}