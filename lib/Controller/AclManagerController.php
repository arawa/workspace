<?php

namespace OCA\Workspace\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;

class AclManagerController extends Controller {
    
    public function __construct($AppName, IRequest $request)
    {
        parent::__construct($AppName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @var string $folderId
     * @var string $gid
     */
    public function addGroupAdvancedPermissions($folderId, $gid){
        shell_exec('php occ groupfolders:permissions ' . $folderId . ' --group ' . $gid . ' -m');
    }
}
