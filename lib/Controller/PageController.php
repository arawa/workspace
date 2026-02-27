<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {
	public function __construct(
		private UserService $userService,
		private IConfig $config,
		private IInitialState $initialState,
		private IUserSession $session,
		private SpaceManager $spaceManager,
		private IGroupManager $groupManager,
	) {
	}

	/**
	 * Application's main page
	 *
	 * @NoAdminRequired
	 * @NOCSRFRequired
	 */
	public function index($path = ''): TemplateResponse {
		if (strpos($path, 'api/v') === 0) {
			// avoid non existing API routes to be handled by this controller
			// (because of catchall route)
			throw new NotFoundException('Not found');
		}

		Util::addScript(Application::APP_ID, 'workspace-main');		// js/workspace-main.js
		Util::addStyle(Application::APP_ID, 'workspace-style');		// css/workspace-style.css

		$this->initialState->provideInitialState('userSession', $this->session->getUser()?->getUID());
		$this->initialState->provideInitialState('isUserGeneralAdmin', $this->userService->isUserGeneralAdmin());
		$this->initialState->provideInitialState('canAccessApp', $this->userService->canAccessApp());
		$this->initialState->provideInitialState('aclInheritPerUser', $this->config->getAppValue('groupfolders', 'acl-inherit-per-user', 'false') === 'true');

		$currentUser = $this->session->getUser();
		$generalManagerGroup = $this->groupManager->get(ManagersWorkspace::GENERAL_MANAGER);

		if ($generalManagerGroup->inGroup($currentUser)) {
			$count = $this->spaceManager->countWorkspaces();
		} else {
			$count = $this->spaceManager->countWorkspaces(uid: $currentUser->getUID());
		}

		$this->initialState->provideInitialState('countWorkspaces', $count);

		// templates/index.php
		return new TemplateResponse(
			'workspace',
			'index'
		);
	}
}
