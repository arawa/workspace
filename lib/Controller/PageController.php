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

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	// TODO: Move them to lib/Application.php
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
	 * Application's main page
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		Util::addScript(Application::APP_ID, 'workspace-main');		// js/workspace-main.js
		Util::addStyle(Application::APP_ID, 'workspace-style');		// css/workspace-style.css
	
		return new TemplateResponse('workspace', 'index');  	// templates/index.php
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
				'role' => 'user', // by default, users get the 'user' role
				'subtitle' => $user->getEmailAddress(), // for the Avatar compoments
				'user' => $user->getDisplayName(), // for the Avatar components
			];
		}

		// return info
		return new JSONResponse($data);
	}

}
