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
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCA\Workspace\Exceptions\NotFoundException;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCA\Workspace\Attribute\WorkspaceManagerRequired;

/**
 * @psalm-import-type WorkspaceSpace from ResponseDefinitions
 */
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
	 * Returns a workspace by its ID
	 *
	 * @param int $id Represents the ID of a workspace
	 * @return DataResponse<Http::STATUS_OK, WorkspaceSpace, array{}>
	 * @throws OCSNotFoundException when no groupfolder is associated with the given space ID
	 * @throws OCSException for all unknown errors
	 *
	 * 200: Workspace returned
	 *
	 */
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'GET',
		url: '/api/v1/space/{id}',
		requirements: ['id' => '\d+']
	)]
	public function find(int $id): DataResponse {
		try {
			$space = $this->spaceManager->get($id);
		} catch (\Exception $e) {
			if ($e instanceof NotFoundException) {
				throw new OCSNotFoundException($e->getMessage());
			}

			throw new OCSException($e->getMessage());
		}

		return new DataResponse($space, Http::STATUS_OK);
	}

	/**
	 * Return workspaces with the possibility to filter by name
	 * 
	 * @param string|null $name Optional filter to return workspaces by name
	 * @return DataResponse<Http::STATUS_OK, WorkspaceSpace, array{}>
	 * 
	 * 200: Succesfully retrieved workspaces
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/spaces')]
	public function findAll(?string $name): Response {
		$workspaces = $this->spaceManager->findAll();

		// We only want to return those workspaces for which the connected user is a manager
		if (!$this->userService->isUserGeneralAdmin()) {
			$filteredWorkspaces = array_values(array_filter($workspaces, function ($workspace) {
				return $this->userService->isSpaceManagerOfSpace($workspace);
			}));
			$workspaces = $filteredWorkspaces;
		}

		if (!is_null($name)) {
			$filterToLower = strtolower($name);
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
