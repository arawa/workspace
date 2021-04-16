<?php
namespace OCA\Workspace\Controller;

use OCP\IRequest;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {
	
	/** @var string */
	private $userId;

	protected $userManager;

	protected $groupManager;

	private $ESPACE_MANAGER_01 = "GE-";
	private $ESPACE_MANAGER_02 = "Manager_";
	private $ESPACE_MANAGER_03 = "_GE";
	private $ESPACE_USERS_01 = "_U";
	private $ESPACE_USERS_02 = "Users_";
	private $ESPACE_USERS_03 = "U-";

	public function __construct($AppName, IRequest $request, $UserId, IUserManager $users, IGroupManager $group){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->userManager = $users;
		$this->groupManager = $group;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {

		$userObject = $this->userManager->get($this->userId);
		$userId = $userObject->getUID();

		$usersManager = $this->userManager->searchDisplayName('');
		
		$allUsersByEspaceManagerGroup = [];

		$allGEGroups = $this->groupManager->search($this->ESPACE_MANAGER_01);

		for ($i = 0; $i < count($usersManager) ; $i++ ) {
			for($j = 0; $j < count($allGEGroups); $j++){
				if( $this->groupManager->isInGroup($usersManager[$i]->getUID(), $allGEGroups[$j]->getGID()) ){
					$allUsersByEspaceManagerGroup[] = [ "uid" => $usersManager[$i]->getUID(), "email_address" => $usersManager[$i]->getEMailAddress(), "gid" => $allGEGroups[$j]->getGID() ];
				}
			}
		}
		
		return new TemplateResponse('workspace', 'index', [ "users" => $usersManager, "usersByEspaceManagerGroup" => $allUsersByEspaceManagerGroup ]);  // templates/index.php
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * TODO: Find a solution to use this method.
	 */
	public function errorAccess(){
		return new TemplateResponse('workspace', 'errorAccess');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getSubGoupCreate(){

		return new TemplateResponse('workspace', 'subgroupCreation');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function editGeneralManagerGroup(){

		$usersByGroup = [];

		$users = $this->userManager->searchDisplayName('');

		for($i = 0 ; $i < count($users); $i++){

			$userGroups = $this->groupManager->getUserGroups($users[$i]);

			$usersByGroup[$i]['uid'] = $users[$i]->getUID();
			$usersByGroup[$i]['gids'] = [];

			foreach($userGroups as $key => $value){
				if( preg_match("/^$this->ESPACE_MANAGER_01/", $key) === 1 || 
					preg_match("/^$this->ESPACE_MANAGER_02/", $key) === 1 ||
					preg_match("/$this->ESPACE_MANAGER_03$/", $key) === 1 ||
					preg_match("/$this->ESPACE_USERS_01$/", $key) === 1 ||
					preg_match("/^$this->ESPACE_USERS_02/", $key) === 1 ||
					preg_match("/^$this->ESPACE_USERS_03/", $key) === 1
				)
				{
					$usersByGroup[$i]['gids'][] = $key;
				}
			};
		}

			return new TemplateResponse('workspace', 'changeManagerGeneral', [ 'users' => $usersByGroup ]);
	}

}
