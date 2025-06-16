<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2025 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCSController;
use OCA\Workspace\Space\SpaceManager;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCA\Workspace\Attribute\WorkspaceManagerRequired;

class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private SpaceManager $spaceManager,
		private UserService $userService,
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return Response<{
	 * 	id: int,
	 * 	mount_point: string,
	 * 	groups: array,
	 * 	quota: int,
	 * 	size: int,
	 * 	acl: bool,
	 * 	manage: array,
	 * 	groupfolder_id: int,
	 * 	name: string,
	 * 	color_code: string,
	 * 	users: array,
	 * 	userCount: int,
	 * 	added_groups: array
	 * }, Http::STATUS_OK>
	 *
	 * 200: Workspaces returned
	 */
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/spaces')]
	public function findAll(): Response {
		$filterByName = $this->request->getParam('name');

		$workspaces = $this->spaceManager->findAll();

		// We only want to return those workspaces for which the connected user is a manager
		if (!$this->userService->isUserGeneralAdmin()) {
			$filteredWorkspaces = array_values(array_filter($workspaces, function ($workspace) {
				return $this->userService->isSpaceManagerOfSpace($workspace);
			}));
			$workspaces = $filteredWorkspaces;
		}

		if (!is_null($filterByName)) {
			$filterToLower = strtolower($filterByName);
			$pattern = "/.*{$filterToLower}.*/";
			
			$workspacesFiltered = [];
			foreach ($workspaces as $workspace) {
				if (preg_match($pattern, strtolower($workspace['name']))) {
					$workspacesFiltered[] = $workspace;
				}
			}

			$workspaces = $workspacesFiltered ? $workspacesFiltered : null;
		}

		return new DataResponse($workspaces, Http::STATUS_OK);
	}
}
