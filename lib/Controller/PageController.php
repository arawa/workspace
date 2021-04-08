<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCP\IRequest;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Controller;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Util;

class PageController extends Controller {

  /** @var IUserManager */
	private $userManager;

	public function __construct(
      IRequest $request,
			IUserManager $userManager){
		
    parent::__construct(Application::APP_ID, $request);
		$this->userManager = $userManager;
    
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
			];
		}

		// return info
		return new JSONResponse($data);

  }

}
