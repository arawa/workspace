<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCP\IRequest;
use OCP\IGroupManager;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Util;

class PageController extends Controller {
	/** @var string */
	private $userId;

  /** @var IUserManager */
	private $userManager;

	protected $groupManager;

	private $ESPACE_MANAGER = "GE-";

	public function __construct($AppName, IRequest $request, $UserId, IUserManager $users, IGroupManager $group){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->userManager = $users;
		$this->groupManager = $group;
	}

	/**
	 * Application's main page
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		
		// TODO: Move my algo in a new Class & funtion (?)

		$userObject = $this->userManager->get($this->userId);
		$userId = $userObject->getUID();

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
		
		Util::addScript(Application::APP_ID, 'workspace-main');		// js/workspace-main.js
		Util::addStyle(Application::APP_ID, 'workspace-style');		// css/workspace-style.css

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
	 * Returns a list of users whose name matches $term
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $term
	 * @return JSONResponse
	 */
	public function autoComplete(string $term) {
		// lookup users
		$users = $this->userManager->searchDisplayName($term);

		// transform in a format suitable for the app
		$data = [];
		foreach($users as $user) {
			$data[] = [
				'displayName' => $user->getDisplayName(),
				'email' => $user->getEmailAddress(),
			];
		}

		// return info
		return new JSONResponse($data);

  }

}
