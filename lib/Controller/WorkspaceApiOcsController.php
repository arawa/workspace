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

use OCA\Workspace\Attribute\WorkspaceManagerRequired;
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Attribute\GeneralManagerRequired;
use OCA\Workspace\Attribute\RequireExistingSpace;
use OCA\Workspace\Attribute\SpaceIdNumber;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type WorkspaceSpace from ResponseDefinitions
 */
class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private LoggerInterface $logger,
		private SpaceManager $spaceManager,
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
	 * @param int $id of workspace to delete
	 */
	#[GeneralManagerRequired]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'DELETE',
		url: '/api/v1/spaces/{id}',
		requirements: ['id' => '\d+']
	)]
	public function delete(int $id): Response {
		$space = $this->spaceManager->get($id);
		$groups = [];

		foreach (array_keys($space['groups']) as $group) {
			$groups[] = $group;
		}

		$this->spaceManager->remove($id);

		$this->logger->info("The {$space['name']} workspace with id {$space['id']} is deleted");

		return new DataResponse(
			[
				'name' => $space['name'],
				'groups' => $groups,
				'space_id' => $space['id'],
				'groupfolder_id' => $space['groupfolder_id'],
				'state' => 'delete'
			],
			Http::STATUS_OK
		);
	}
}
