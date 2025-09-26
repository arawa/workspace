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
use OCA\Workspace\Attribute\RequireExistingGroup;
use OCA\Workspace\Attribute\RequireExistingSpace;
use OCA\Workspace\Attribute\SpaceIdNumber;
use OCA\Workspace\Attribute\WorkspaceManagerRequired;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Service\Group\GroupsWorkspace;
use OCA\Workspace\Service\Group\GroupsWorkspaceService;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\Params\WorkspaceEditParams;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Validator\WorkspaceEditParamsValidator;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type WorkspaceSpace from ResponseDefinitions
 * @psalm-import-type WorkspaceFindGroups from ResponseDefinitions
 * @psalm-import-type WorkspaceConfirmationMessage from ResponseDefinitions
 * @psalm-import-type WorkspaceUsersList from ResponseDefinitions
 * @psalm-import-type WorkspaceUserDefinition from ResponseDefinitions
 */
class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private LoggerInterface $logger,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private SpaceManager $spaceManager,
		private WorkspaceEditParamsValidator $editParamsValidator,
		private GroupsWorkspaceService $groupsWorkspaceService,
		private SpaceMapper $spaceMapper,
		private UserService $userService,
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Return workspaces (optional filtering by name)
	 *
	 * @param string|null $name Optional filter to return workspaces by name
	 * @return DataResponse<Http::STATUS_OK, WorkspaceSpace, array{}>
	 *
	 * 200: Succesfully retrieved workspaces
	 */
	#[OpenAPI(tags: ['workspace'])]
	#[NoAdminRequired]
	#[ApiRoute(verb: 'GET', url: '/api/v1/spaces')]
	public function findAll(?string $name): DataResponse {
		$workspaces = $this->spaceManager->findAll();

		if (is_null($workspaces)) {
			return new DataResponse(null, Http::STATUS_OK);
		}

		// We only want to return those workspaces for which the connected user is a manager
		if (!$this->userService->isUserGeneralAdmin()) {
			$filteredWorkspaces = array_values(array_filter($workspaces, function ($workspace) {
				return $this->userService->isSpaceManagerOfSpace($workspace);
			}));
			$workspaces = $filteredWorkspaces;
		}

		if (!is_null($name)) {
			$filterToLower = strtolower($name);

			$workspacesFiltered = [];
			foreach ($workspaces as $workspace) {
				if (strpos(strtolower($workspace['name']), $filterToLower) !== false) {
					$workspacesFiltered[] = $workspace;
				}
			}

			$workspaces = $workspacesFiltered ? $workspacesFiltered : null;
		}

		return new DataResponse($workspaces, Http::STATUS_OK);
	}

	/**
	 * Returns a workspace by its ID
	 *
	 * @param int $id Represents the ID of a workspace
	 * @return DataResponse<Http::STATUS_OK, WorkspaceSpace, array{}>
	 * @throws OCSNotFoundException when no workspace is associated with the given space ID
	 * @throws OCSException for all unknown errors
	 *
	 * 200: Workspace returned
	 * 404: Workspace not found
	 *
	 */
	#[OpenAPI(tags: ['workspace'])]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'GET',
		url: '/api/v1/spaces/{id}',
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

		if (empty($space)) {
			throw new OCSNotFoundException('No workspace found with id ' . $id);
		}

		return new DataResponse($space, Http::STATUS_OK);
	}

	/**
	 * Edit workspace name, color and quota
	 *
	 * @param int $id Represents the ID of a workspace
	 * @param string|null $name Workspace name (optional)
	 * @param string|null $color Workspace color (optional)
	 * @param int|null $quota Workspace quota in bytes (optional, -3 means unlimited, 0 means no quota)
	 * @return DataResponse<Http::STATUS_OK, WorkspaceSpace, array{}>
	 * @throws OCSNotFoundException when no groupfolder is associated with the given space ID
	 * @throws OCSException for all unknown errors
	 *
	 * 200: Workspace returned
	 * 404: Workspace not found
	 *
	 */
	#[OpenAPI(tags: ['workspace'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[GeneralManagerRequired]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'PATCH',
		url: '/api/v1/spaces/{id}',
		requirements: ['id' => '\d+']
	)]
	public function edit(int $id, ?string $name, ?string $color = null, ?int $quota = null): DataResponse {
		$toSet = array_merge(WorkspaceEditParams::DEFAULT, [
			'name' => $name,
			'color' => $color,
			'quota' => $quota
		]);

		$this->editParamsValidator->validate($toSet);

		if (!is_null($toSet['color'])) {
			$this->spaceManager->setColor($id, $toSet['color']);
		}

		if (!is_null($toSet['name'])) {
			$space = $this->spaceManager->get($id);
			if (strtolower($space['name']) !== strtolower($toSet['name'])) {
				$this->spaceManager->renameGroups($id, $space['name'], $toSet['name']);
				$this->spaceManager->rename($id, $toSet['name']);
			} else {
				$this->logger->info("The workspace {$toSet['name']} is already named as {$space['name']}");
				$toSet['name'] = $space['name']; // when case is different
			}
		}

		if (!is_null($toSet['quota'])) {
			$this->spaceManager->setQuota($id, $toSet['quota']);
		}

		return new DataResponse($toSet, Http::STATUS_OK);
	}

	/**
	 * Returns the users from a workspace
	 *
	 * @param int $id Represents the ID of the workspace
	 * @return DataResponse<Http::STATUS_OK, WorkspaceUsersList, array{}>
	 *
	 * 200: Users of the specified workspace returned successfully.
	 */
	#[OpenAPI(tags: ['workspace-users'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'GET',
		url: '/api/v1/spaces/{id}/users',
		requirements: ['id' => '\d+']
	)]
	public function findUsersById(int $id): Response {
		try {
			$users = $this->spaceManager->findUsersById($id);
		} catch (\Exception $e) {
			if ($e instanceof NotFoundException) {
				throw new OCSNotFoundException($e->getMessage());
			}

			throw new OCSException($e->getMessage(), $e->getCode());
		}

		return new DataResponse($users, Http::STATUS_OK);
	}

	/**
	 * Create a new workspace
	 *
	 * @param string $name The workspace name
	 * @return DataResponse<Http::STATUS_CREATED, WorkspaceSpace, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, null, array{}>|DataResponse<Http::STATUS_CONFLICT, null, array{}>
	 * @throws OCSException for all unknown errors
	 *
	 * 201: Workspace created successfully
	 * 400: Invalid workspace name
	 * 409: Workspace with this name already exists
	 */
	#[OpenAPI(tags: ['workspace'])]
	#[GeneralManagerRequired]
	#[NoAdminRequired]
	#[ApiRoute(verb: 'POST', url: '/api/v1/spaces')]
	public function create(string $name): DataResponse {
		try {
			$space = $this->spaceManager->create($name);
			$this->logger->info("The workspace {$name} is created");
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}

		return new DataResponse($space, Http::STATUS_CREATED);
	}

	/**
	 * Remove a workspace by id
	 *
	 * @param int $id of a workspace to delete
	 * @return DataResponse<Http::STATUS_NO_CONTENT, array{}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 204: Workspace deleted successfully
	 * 404: Workspace not found
	 */
	#[OpenAPI(tags: ['workspace'])]
	#[GeneralManagerRequired]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'DELETE',
		url: '/api/v1/spaces/{id}',
		requirements: ['id' => '\d+']
	)]
	public function delete(int $id): DataResponse {
		$space = $this->spaceManager->get($id);
		$groups = [];

		foreach (array_keys($space['groups']) as $group) {
			$groups[] = $group;
		}

		$this->spaceManager->remove($id);

		$this->logger->info("The {$space['name']} workspace with id {$space['id']} is deleted");

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}

	/**
	 * Returns the groups associated with a workspace
	 *
	 * @param int $id Represents the ID of the workspace
	 * @return DataResponse<Http::STATUS_OK, WorkspaceFindGroups, array{}>
	 *
	 * 200: Groups of the specified workspace returned successfully.
	 */
	#[OpenAPI(tags: ['workspace-groups'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'GET',
		url: '/api/v1/spaces/{id}/groups',
		requirements: ['id' => '\d+'],
	)]
	public function findGroupsBySpaceId(int $id): DataResponse {
		$groups = $this->spaceManager->findGroupsBySpaceId($id);
		$this->logger->info("Successfully find groups from the {$id} workspace.");

		return new DataResponse($groups, Http::STATUS_OK);
	}

	/**
	 * Adds users to the workspace by id
	 *
	 * @param int $id Represents the ID of the workspace
	 * @param list<string> $uids Represents the user uids to add to the workspace
	 * @return DataResponse<Http::STATUS_OK, WorkspaceConfirmationMessage, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 200: Confirmation message indicating that users have been added successfully.
	 * 404: User not found in the instance.
	 */
	#[OpenAPI(tags: ['workspace-users'])]
	#[WorkspaceManagerRequired]
	#[RequireExistingSpace]
	#[SpaceIdNumber]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'POST',
		url: '/api/v1/spaces/{id}/users',
		requirements: ['id' => '\d+']
	)]
	public function addUsersInWorkspace(int $id, array $uids): DataResponse {
		try {
			$this->spaceManager->addUsersInWorkspace($id, $uids);
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}

		$spacename = $this->spaceMapper->find($id)->getSpaceName();

		$count = count($uids);
		$this->logger->info("{$count} users were added in the {$spacename} workspace with the {$id} id.");

		return new DataResponse([
			'message' => "{$count} users were added in the {$spacename} workspace with the {$id} id."
		], Http::STATUS_OK);
	}

	/**
	 * Remove users from a workspace
	 *
	 * @param int $id Represents the ID of the workspace
	 * @param list<string> $uids Represents the user uids to remove to the workspace
	 * @return DataResponse<Http::STATUS_NO_CONTENT, array{}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 204: Confirmation for users removed from the workspace.
	 * 404: User not found in the instance.
	 */
	#[OpenAPI(tags: ['workspace-users'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'DELETE',
		url: '/api/v1/spaces/{id}/users',
		requirements: ['id' => '\d+']
	)]
	public function removeUsersInWorkspace(int $id, array $uids): DataResponse {
		$this->spaceManager->removeUsersFromWorkspace($id, $uids);
		$uidsStringify = implode(', ', $uids);
		$this->logger->info("These users are removed from the workspace with the id {$id} : {$uidsStringify}");

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}

	/**
	 * Add user as a workspace manager
	 *
	 * @param int $id Represents the ID of the workspace
	 * @param string $uid Represents the user uid to add as a workspace manager
	 * @return DataResponse<Http::STATUS_OK, array{}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{}, array{}>
	 *
	 * 200: Confirmation for users added to the workspace.
	 * 404: User not found in the instance.
	 */
	#[OpenAPI(tags: ['workspace-users'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'POST',
		url: '/api/v1/spaces/{id}/workspace-manager',
		requirements: ['id' => '\d+']
	)]
	public function addUserAsWorkspaceManager(int $id, string $uid): DataResponse {
		$user = $this->userManager->get($uid);

		if (is_null($user)) {
			throw new OCSNotFoundException("The user with the uid {$uid} doesn't exist in your Nextcloud instance.");
		}

		$this->spaceManager->addUserAsWorkspaceManager($id, $uid);
		return new DataResponse(['uid' => $uid], Http::STATUS_OK);
	}

	/**
	 * Remove user as a workspace manager
	 *
	 * @param int $id Represents the ID of the workspace
	 * @param string $uid Represents the user uid to remove as a workspace manager
	 * @return DataResponse<Http::STATUS_NO_CONTENT, array{}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{}, array{}>
	 *
	 * 204: Confirmation for user removed as a workspace manager.
	 * 404: User not found in the instance.
	 */
	#[OpenAPI(tags: ['workspace-users'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[ApiRoute(
		verb: 'DELETE',
		url: '/api/v1/spaces/{id}/workspace-manager',
		requirements: ['id' => '\d+']
	)]
	public function removeUserAsWorkspaceManager(int $id, string $uid): DataResponse {
		$user = $this->userManager->get($uid);

		if (is_null($user)) {
			throw new OCSNotFoundException("The user with the uid {$uid} doesn't exist in your Nextcloud instance.");
		}

		$managerGid = WorkspaceManagerGroup::get($id);
		$managerGroup = $this->groupManager->get($managerGid);


		$this->spaceManager->removeUsersFromWorkspaceManagerGroup($managerGroup, [$user]);

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}

	/**
	 * Create a new subgroup for a workspace
	 *
	 * @param int $id Represents the ID of the workspace
	 * @param string $name The subgroup name
	 * @return DataResponse<Http::STATUS_CREATED, WorkspaceSpace, array{}>|DataResponse<Http::STATUS_CONFLICT, null, array{}>
	 * @throws OCSException for all unknown errors
	 *
	 * 201: Subgroup created successfully
	 * 409: Subgroup with this name already exists
	 */
	#[OpenAPI(tags: ['workspace-groups'])]
	#[NoAdminRequired]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[ApiRoute(
		verb: 'POST',
		url: '/api/v1/spaces/{id}/groups',
		requirements: [
			'id' => '\d+',
		]
	)]
	public function createSubGroup(int $id, string $name): DataResponse {
		try {
			$group = $this->spaceManager->createSubGroup($id, $name);
			return new DataResponse([ 'gid' => $group->getGID() ], Http::STATUS_CREATED);
		} catch (\Exception $exception) {
			throw new OCSException($exception->getMessage(), $exception->getCode());
		}
	}

	/**
	 * Remove users from a subgroup in a workspace
	 *
	 * @param int $id Represents the ID of the workspace
	 * @param string $gid The subgroup id
	 * @param list<string> $uids Represents the user uids to remove from the subgroup
	 * @return DataResponse<Http::STATUS_NO_CONTENT, WorkspaceSpace, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 * @throws OCSException for all unknown errors
	 *
	 * 204: Users removed from subgroup successfully
	 * 404: Subgroup with this id does not exist
	 */
	#[OpenAPI(tags: ['workspace-users'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[RequireExistingGroup]
	#[NoAdminRequired]
	#[WorkspaceManagerRequired]
	#[ApiRoute(
		verb: 'DELETE',
		url: '/api/v1/spaces/{id}/groups/{gid}/users',
		requirements: [
			'id' => '\d+',
			'gid' => '^[A-Za-z0-9\W]+'
		]
	)]
	public function removeUsersFromGroup(int $id, string $gid, array $uids): DataResponse {
		$users = [];
		$group = $this->groupManager->get($gid);
		if ($group === null) {
			throw new OCSNotFoundException("The group with the gid {$gid} does not exist.");
		}

		$usersNotFound = [];
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			if (is_null($user)) {
				$usersNotFound[] = $uid;
			}

			$users[] = $user;
		}

		if (!empty($usersNotFound)) {
			$usersNotFound = array_map(fn ($uid) => "- {$uid}", $usersNotFound);
			$usersNotFound = implode("\n", $usersNotFound);
			throw new OCSNotFoundException("These users not exist in your Nextcloud instance:\n{$usersNotFound}");
		}

		$users = array_map(fn ($uid) => $this->userManager->get($uid), $uids);

		switch ($gid) {
			case GroupsWorkspace::isWorkspaceUserGroupId($gid):
				$space = $this->spaceManager->get($id);
				$this->spaceManager->removeUsersFromUserGroup($space, $group, $users);
				break;
			case GroupsWorkspace::isWorkspaceSubGroup($gid) || GroupsWorkspace::isWorkspaceGroup($group):
				$this->spaceManager->removeUsersFromSubGroup($group, $users);
				break;
			case GroupsWorkspace::isWorkspaceAdminGroupId($gid):
				$this->spaceManager->removeUsersFromWorkspaceManagerGroup($group, $users);
				break;
			default:
				throw new OCSBadRequestException("Your gid {$gid} doesn't come from a workspace");
		}

		// to remove
		$uids = implode(', ', $uids);
		$this->logger->info("Users are removed from the {$gid} group (not the added groups) in the workspace {$id}");

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}

	/**
	 * Add users in a subgroup in a workspace
	 *
	 * @param int $id Represents the ID of the workspace
	 * @param string $gid The subgroup id
	 * @param list<string> $uids Represents the user uids to add to the subgroup
	 * @return DataResponse<Http::STATUS_OK, WorkspaceSpace, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 * @throws OCSException for all unknown errors
	 *
	 * 200: Users added in subgroup successfully
	 * 404: Subgroup with this id does not exist
	 */
	#[OpenAPI(tags: ['workspace-users'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[RequireExistingGroup]
	#[NoAdminRequired]
	#[WorkspaceManagerGroup]
	#[ApiRoute(
		verb: 'POST',
		url: '/api/v1/spaces/{id}/groups/{gid}/users',
		requirements: [
			'id' => '\d+',
			'gid' => '^[A-Za-z0-9\W]+'
		]
	)]
	public function addUsersToGroup(int $id, string $gid, array $uids): Response {
		$users = [];
		$workspace = $this->spaceManager->get($id);
		if ($workspace === null) {
			throw new OCSNotFoundException("The workspace with the id {$id} does not exist.");
		}
		$gids = array_keys($workspace['groups']);
		$spacename = $workspace['name'];

		if (!in_array($gid, $gids)) {
			throw new OCSException("The {$gid} group is not belong to the {$spacename} workspace.");
		}
		$group = $this->groupManager->get($gid);
		if ($group === null) {
			throw new OCSNotFoundException("The group with the gid {$gid} does not exist.");
		}

		$usersNotFound = [];
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			if (is_null($user)) {
				$usersNotFound[] = $uid;
				continue;
			}

			$users[] = $user;
		}

		if (!empty($usersNotFound)) {
			$usersNotFound = array_map(fn ($uid) => "- {$uid}", $usersNotFound);
			$usersNotFound = implode("\n", $usersNotFound);
			throw new OCSNotFoundException("These users don't exist in your Nextcloud instance:\n{$usersNotFound}");
		}

		switch ($gid) {
			case GroupsWorkspace::isWorkspaceUserGroupId($gid):
				$this->spaceManager->addUsersToGroup($group, $users);
				break;
			case GroupsWorkspace::isWorkspaceAdminGroupId($gid):
				$this->spaceManager->addUsersToWorkspaceManagerGroup($workspace, $group, $users);
				break;
			case GroupsWorkspace::isWorkspaceSubGroup($gid) || GroupsWorkspace::isWorkspaceGroup($group):
				$this->spaceManager->addUsersToSubGroup($workspace, $group, $users);
				break;
			default:
				throw new OCSBadRequestException("Your gid {$gid} doesn't come from a workspace");
		}

		$displayname = $group->getDisplayName();
		$count = count($uids);

		$this->logger->info("{$count} users were added in the {$displayname} ({$gid}) group from the {$spacename} workspace ({$id}).");

		return new DataResponse([
			'message' => "{$count} users were added in the {$displayname} ({$gid}) group from the {$spacename} workspace ({$id})."
		], Http::STATUS_OK);
	}

	/**
	 * Remove a subgroup from a workspace
	 * @param int $id Id of the workspace
	 * @param string $gid id of the subgroup to delete
	 * @return DataResponse<Http::STATUS_NO_CONTENT, array{}, array{}>|DataResponse<Http::STATUS_NOT_FOUND, null, array{}>
	 *
	 * 204: Subgroup deleted successfully
	 * 404: Workspace not found
	 */
	#[OpenAPI(tags: ['workspace-groups'])]
	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[RequireExistingGroup]
	#[NoAdminRequired]
	#[WorkspaceManagerGroup]
	#[ApiRoute(
		verb: 'DELETE',
		url: '/api/v1/spaces/{id}/groups/{gid}',
		requirements: ['id' => '\d+']
	)]
	public function removeGroup(int $id, string $gid): Response {
		$group = $this->groupManager->get($gid);

		try {
			$this->groupsWorkspaceService->removeGroup($group);
		} catch (\Exception $e) {
			throw new OCSException($e->getMessage(), $e->getCode());
		}

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}
}
