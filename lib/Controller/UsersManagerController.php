<?php
namespace OCA\Workspace\Controller;

use OCP\AppFramework\Controller;
use OCP\IRequest;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;


class UsersManagerController extends Controller{

    private $groupManager;
    private $userManager;

    public function __construct($AppName, IRequest $request, IGroupManager $groupManager, IUserManager $userManager){
        parent::__construct($AppName, $request);
        $this->groupManager = $groupManager;
        $this->userManager = $userManager;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * @var string $gid
     * @return Array
     */
    public function getUsersWorkSpace($gid){
        $group = $this->groupManager->get($gid);

        if( $group === null ){
            return [];
        }

        $usersFromGroup = $group->getUsers();

        $role = "undefined";

        if(
            preg_match("/^GE-/", $gid ) === 1 ||
            preg_match("/_Manager$/", $gid ) === 1 ||
            preg_match("/_GE$/", $gid ) === 1
        )
        {
            $role = "admin";
        }
        elseif(
            preg_match("/^U-/", $gid ) === 1 ||
            preg_match("/_U$/", $gid ) === 1 ||
            preg_match("/_User$/", $gid ) === 1
        )
        {
            $role = "user";
        }

        $users = [];

        foreach($usersFromGroup as $username => $values){
            $users[] = [
                'username' => $this->userManager->get($username)->getUID(),
                'email' => $this->userManager->get($username)->getEmailAddress(),
                'group' => $gid,
                'role' => $role,
            ];
        }

        return new JSONResponse($users);
    }

}
