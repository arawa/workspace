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
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
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
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type WorkspaceSpace from ResponseDefinitions
 * @psalm-import-type WorkspaceSpaceDelete from ResponseDefinitions
 * @psalm-import-type WorkspaceFindGroups from ResponseDefinitions
 */
class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private LoggerInterface $logger,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
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

	/**
	 * Create a new workspace
	 *
	 * @param string $spacename Represents the workspace name
	 * @return Response<Http::STATUS_CREATED, WorkspaceSpace>
	 * @throws OCSException for all unknown errors
	 *
	 * 201: Workspace created successfully
	 */
	#[GeneralManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'POST',
		url: '/api/v1/spaces',
	)]
	public function create(string $spacename): Response {
		try {
			$space = $this->spaceManager->create($spacename);
			$this->logger->info("The workspace {$spacename} is created");
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}

		return new DataResponse($space, Http::STATUS_CREATED);
}

	/**
	 * Remove a workspace by id
	 *
	 * @param int $id of a workspace to delete
	 * @return DataResponse<Http::STATUS_OK, WorkspaceSpaceDelete, array{}>
	 *
	 * 200: Workspace deleted successfully
	 */
	#[GeneralManagerRequired]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'DELETE',
		url: '/api/v1/space/{id}',
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
				'id' => $space['id'],
				'groupfolder_id' => $space['groupfolder_id'],
				'state' => 'delete'
			],
			Http::STATUS_OK
		);
	}

	/**
	 * Returns the groups associated with a workspace.
	 * 
	 * @param int $id Represents the ID of the workspace.
	 * @return DataResponse<Http::STATUS_OK, WorkspaceFindGroups, array{}>
	 * 
	 * 200: Groups of the specified workspace returned successfully.
	 */
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'GET',
		url: '/api/v1/space/{id}/groups',
		requirements: ['id' => '\d+'],
	)]
	public function findGroupsBySpaceId(int $id): Response {
		$groups = $this->spaceManager->findGroupsBySpaceId($id);
		$this->logger->info("Successfully find groups from the {$id} workspace.");

		return new DataResponse($groups, Http::STATUS_OK);
	}

	/**
	 * Remove users from a workspace
	 * 
	 * @param int $id Represents the OD of the workspace.
	 * @param list<string> $uids Represents the user uids to remove to the workspace.
	 * @return DataResponse<Http::STATUS_NO_CONTENT, array{}, array{}>
	 * 
	 * 204: Confirmation for users removed from the workspace.
	 */
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'DELETE',
		url: '/api/v1/space/{id}/users',
		requirements: ['id' => '\d+']
	)]
	public function removeUsersInWorkspace(int $id, array $uids): Response {
		$this->spaceManager->removeUsersFromWorkspace($id, $uids);
		$uidsStringify = implode(', ', $uids);
		$this->logger->info("These users are removed from the workspace with the id {$id} : {$uidsStringify}");

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}

	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'POST',
		url: '/api/v1/space/{id}/workspace-manager',
		requirements: ['id' => '\d+']
	)]
	public function addUserAsWorkspaceManager(int $id, string $uid): Response {
		$user = $this->userManager->get($uid);

		if (is_null($user)) {
			throw new OCSNotFoundException("The user with the uid {$uid} doesn't exist in your Nextcloud instance.");
		}

		$this->spaceManager->addUserAsWorkspaceManager($id, $uid);
		return new DataResponse(['uid' => $uid], Http::STATUS_OK);
	}

	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'DELETE',
		url: '/api/v1/space/{id}/workspace-manager',
		requirements: ['id' => '\d+']
	)]
	public function removeUserAsWorkspaceManager(int $id, string $uid): Response {
		$user = $this->userManager->get($uid);

		if (is_null($user)) {
			throw new OCSNotFoundException("The user with the uid {$uid} doesn't exist in your Nextcloud instance.");
		}

		$managerGid = WorkspaceManagerGroup::get($id);
		$managerGroup = $this->groupManager->get($managerGid);


		$this->spaceManager->removeUsersFromWorkspaceManagerGroup($managerGroup, [$user]);

		return new DataResponse([], Http::STATUS_OK);
	}

}
