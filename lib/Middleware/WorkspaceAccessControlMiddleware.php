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

namespace OCA\Workspace\Middleware;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Middleware;
use OCP\IURLGenerator;
use OCP\Util;

class WorkspaceAccessControlMiddleware extends Middleware {

	public function __construct(
		private IURLGenerator $urlGenerator,
		private UserService $userService
	) {
	}

	public function beforeController($controller, $methodName) {
		// Checks if user is member of the General managers group
		if ($this->userService->isUserGeneralAdmin()) {
			return;
		}

		// Checks if user if member of a Space managers group
		if ($this->userService->isSpaceManager()) {
			return;
		}

		throw new AccessDeniedException();
	}

	// TODO: Find a solution to use this method.
	public function afterException($controller, $methodName, \Exception $exception) {
		if ($exception instanceof AccessDeniedException) {
			Util::addScript(Application::APP_ID, 'workspace-main');		// js/workspace-main.js
			Util::addStyle(Application::APP_ID, 'workspace-style');		// css/workspace-style.css

			return new TemplateResponse("workspace", "index", ['isUserGeneralAdmin' => $this->userService->isUserGeneralAdmin(), 'canAccessApp' => false ]);
		}
	}
}
