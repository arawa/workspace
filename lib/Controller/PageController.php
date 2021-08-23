<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Util;

class PageController extends Controller {

	/** @var UserService */
	private $userService;

	public function __construct(
		UserService $userService
	)
	{
		$this->userService = $userService;
	}

	/**
	 * Application's main page
	 *
	 * @NoAdminRequired
	 * @NOCSRFRequired
	 */
	public function index() {
		Util::addScript(Application::APP_ID, 'workspace-main');		// js/workspace-main.js
		Util::addStyle(Application::APP_ID, 'workspace-style');		// css/workspace-style.css
	
		return new TemplateResponse('workspace', 'index', ['isUserGeneralAdmin' => $this->userService->isUserGeneralAdmin()]); 	// templates/index.php
	}

}
