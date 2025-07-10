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

use OCA\Workspace\Attribute\GeneralManagerRequired;
use OCA\Workspace\Attribute\RequireExistingSpace;
use OCA\Workspace\Attribute\SpaceIdNumber;
use OCA\Workspace\Attribute\WorkspaceManagerRequired;
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Service\Params\WorkspaceEditParams;
use OCA\Workspace\Service\Validator\WorkspaceEditParamsValidator;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

/**
 * @psalm-import-type WorkspaceSpace from ResponseDefinitions
 */
class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private SpaceManager $spaceManager,
		private WorkspaceEditParamsValidator $editParamsValidator,
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

	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[GeneralManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'PATCH',
		url: '/api/v1/space/{id}',
		requirements: ['id' => '\d+']
	)]
	public function edit(int $id, array $params): Response {
		$toSet = array_merge(WorkspaceEditParams::DEFAULT, $params);

		$this->editParamsValidator->validate($toSet);

		if (!is_null($toSet['color'])) {
			$this->spaceManager->setColor($id, $toSet['color']);
		}

		if (!is_null($toSet['name'])) {
			$space = $this->spaceManager->get($id);
			$this->spaceManager->renameGroups($id, $space['name'], $toSet['name']);
			$this->spaceManager->rename($id, $toSet['name']);
		}

		if (!is_null($toSet['quota'])) {
			$this->spaceManager->setQuota($id, $toSet['quota']);
		}

		return new DataResponse($toSet, Http::STATUS_OK);
	}

	#[NoAdminRequired]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[FrontpageRoute(
		verb: 'POST',
		url: '/api/v1/space/{id}/groups',
		requirements: [
			'id' => '\d+',
		]
	)]
	public function createSubGroup(int $id, string $groupname): Response {
		try {
			$group = $this->spaceManager->createSubGroup($id, $groupname);
			return new DataResponse([ 'gid' => $group->getGID() ], Http::STATUS_CREATED);
		} catch(\Exception $exception) {
			throw new OCSException($exception->getMessage(), $exception->getCode());
		}
	}
}
