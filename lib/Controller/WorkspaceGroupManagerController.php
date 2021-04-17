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

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function removeUserToGroup($uid, $gid){

        $response = [ 
            "response" => "",
            "code" => "",
            "status" => "pending",
            "command" => 'DELETE',
            "target" => "Group",
            "uid" => $uid,
            "gid" => $gid,
            "message" => "",
        ];

        $group = $this->groupManager->get($gid);

        $user = $this->userManager->get($uid);

        if($group->removeUser($user) === null){
            $response['response'] = "ok";
            $response['code'] = 200;
            $response['status'] = "statefull";
            $response['message'] = "Remove user from the group : success !";
            
        }
        // TODO: 
        //  1) Find a solution to send a json error.
        //  2) Check if is it the good code.
        // else{application/json
        //     $response['response'] = "error";
        //     $response['code'] = 500;
        //     $response['status'] = "error";
        //     $response['message'] = "Cannot find the ressource : gid or uid.";
            
        // }


        return new JSONResponse( $response, Http::STATUS_OK);
    }

}