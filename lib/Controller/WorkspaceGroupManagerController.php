<?php

namespace OCA\Workspace\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IGroupManager;



class WorkspaceGroupManagerController extends Controller {

    /** @var string */
    private $userId;

    private $groupManager;

    private $userManager;

    public function __construct($AppName, IRequest $request, $userId, IGroupManager $groupManager, IUserManager $userManager)
    {
        parent::__construct($AppName, $request);
        $this->userId = $userId;
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function addUserGroupUser($uid, $gid){
        $group = $this->groupManager->get($gid);
        
        $user = $this->userManager->get($uid);

        return new JSONResponse([ 'response' => $group->addUser($user) ], Http::STATUS_OK);

    }

}