<?php
namespace OCA\Workspace\Controller;

use OCP\IRequest;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;
use OCA\Workspace\Middleware\GeneralManagerMiddleware;
// use OCA\Workspace\AppInfo\Application;


class PageController extends Controller {
	
	/** @var string */
	private $userId;

	protected $userManager;

	protected $groupManager;

	private $ESPACE_MANAGER = "GE-";

	public function __construct($AppName, IRequest $request, $UserId, IUserManager $users, GeneralManagerMiddleware $middleware, IGroupManager $group){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->userManager = $users;
		$this->groupManager = $group;
		$this->middleware = $middleware;
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

		$this->middleware->beforeController(__CLASS__, __FUNCTION__, $userId);

		$usersManager = $this->userManager->searchDisplayName('');
		
		$allUsersByEspaceManagerGroup = [];

		$allGEGroups = $this->groupManager->search($this->ESPACE_MANAGER);

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

}
