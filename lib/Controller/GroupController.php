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

use OCA\Workspace\GroupsWorkspace;
use OCA\Workspace\ManagersWorkspace;
use OCA\Workspace\Service\Group\GroupFolder\GroupFolderManage;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\GroupsWorkspaceService;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Service\User\UserWorkspace;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserManager;

class GroupController extends Controller {
	private GroupsWorkspaceService $groupsWorkspace;
	private IGroupManager $groupManager;
	private ILogger $logger;
	private IUserManager $userManager;
	private UserFormatter $userFormatter;
	private UserService $userService;
	private UserWorkspace $userWorkspace;

	public function __construct(
		GroupsWorkspaceService $groupsWorkspace,
		IGroupManager $groupManager,
		ILogger $logger,
		IUserManager $userManager,
		UserFormatter $userFormatter,
		UserService $userService,
		UserWorkspace $userWorkspace
	) {
		$this->groupManager = $groupManager;
		$this->groupsWorkspace = $groupsWorkspace;
		$this->logger = $logger;
		$this->userFormatter = $userFormatter;
		$this->userManager = $userManager;
		$this->userService = $userService;
		$this->userWorkspace = $userWorkspace;
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Creates a group
	 * NB: This function could probably be abused by space managers to create arbitrary group. But, do we really care?
	 *
	 * @var string $gid
	 * @var string $spaceId for Middleware
	 *
	 * @return @JSONResponse
	 */
	public function create($gid) {
		if (!is_null($this->groupManager->get($gid))) {
			return new JSONResponse(['Group ' + $gid + ' already exists'], Http::STATUS_FORBIDDEN);
		}

		// Creates group
		$NCGroup = $this->groupManager->createGroup($gid);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Could not create group ' + $gid], Http::STATUS_FORBIDDEN);
		}

		return new JSONResponse([
			'group' => [
				'gid' => $NCGroup->getGID(),
				'displayName' => $NCGroup->getDisplayName()
			]
		]);
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Deletes a group
	 * Cannot delete GE- and U- groups (This is on-purpose)
	 *
	 * @var string $gid
	 * @var string $spaceId
	 *
	 * @return @JSONResponse
	 */
	public function delete($gid, $spaceId) {
		// TODO Use groupfolder api to retrieve workspace group.
		if (substr($gid, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(['You may only delete workspace groups of this space (ie: group\'s name does not end by the workspace\'s ID)'], Http::STATUS_FORBIDDEN);
		}

		// Delete group
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Group ' + $gid + ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}
		$NCGroup->delete();

		return new JSONResponse([
			'gid' => $gid,
			'state' => 'deleted',
			'spaceId' => $spaceId,
			'status' => Http::STATUS_OK
		]);
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Renames a group
	 * Cannot rename GE- and U- groups (This is on-purpose)
	 *
	 * @var string $gid ID of the group to be renamed
	 * @var string $newGroupName The group's new name
	 * @var string $spaceId
	 *
	 * @return @JSONResponse
	 */
	public function rename($newGroupName, $gid, $spaceId) {
		// TODO Use groupfolder api to retrieve workspace group.
		if (substr($gid, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(
				['You may only rename workspace groups of this space (ie: group\'s name does not end by the workspace\'s ID)'],
				Http::STATUS_FORBIDDEN
			);
		}

		if (substr($newGroupName, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(
				['Workspace groups must ends with the ID of the space they belong to'],
				Http::STATUS_FORBIDDEN
			);
		}

		// Rename group
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Group ' . $gid . ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}
		$NCGroup->setDisplayName($newGroupName);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 * @NoCSRFRequired
	 * Adds a user to a group.
	 * The function automaticaly adds the user the the corresponding workspace's user group, and to the application
	 * manager group when we are adding a workspace manager
	 *
	 * @var string $gid
	 * @var string $user
	 *
	 * @return @JSONResponse
	 */
	public function addUser($spaceId, $gid, $user) {
		// Makes sure group exist
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			// In some cases, frontend might give a group's displayName rather than its gid
			$NCGroup = $this->groupManager->search($gid);
			if (empty($NCGroup)) {
				return new JSONResponse(['Group ' + $gid + ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
			}
			$NCGroup = $NCGroup[0];
		}

		// Adds user to group
		$NCUser = $this->userManager->get($user);
		$NCGroup->addUser($NCUser);

		// Adds the user to the application manager group when we are adding a workspace manager
		if ($gid === GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER. $spaceId) {
			$workspaceUsersGroup = $this->groupManager->get(ManagersWorkspace::WORKSPACES_MANAGERS);
			if (!is_null($workspaceUsersGroup)) {
				$workspaceUsersGroup->addUser($NCUser);
			} else {
				$NCGroup->removeUser($NCUser);
				return new JSONResponse(['Generar error: Group ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' does not exist'],
					Http::STATUS_EXPECTATION_FAILED);
			}
		}

		// Adds user to workspace user group
		// This must be the last action done, when all other previous actions have succeeded
		$UGroup = $this->groupManager->get(GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_USERS . $spaceId);
		$UGroup->addUser($NCUser);

		return new JSONResponse(['message' => 'The user ' . $user . ' is added in the ' . $gid . ' group'], Http::STATUS_NO_CONTENT);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @SpaceAdminRequired
	 *
	 * Removes a user from a group
	 * The function also remove the user from all workspace 'subgroup when the user is being removed from the U- group
	 * and from the WorkspacesManagers group when the user is being removed from the GE- group
	 *
	 * @param object|string $space
	 * @var string $gid
	 * @var string $user
	 *
	 * @return JSONResponse
	 */
	public function removeUser($space, $gid, $user) {
		if (gettype($space) === 'string') {
			$space = json_decode($space, true);
		}

		$this->logger->debug('Removing user ' . $user . ' from group ' . $gid);

		// Makes sure group exist
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			$this->logger->error('Group ' . $gid . ' does not exist');
			return new JSONResponse(['Group ' . $gid . ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}

		// Removes user from group(s)
		$NCUser = $this->userManager->get($user);
		$groups = [];
		if ($gid === GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_USERS . $space['id']) {
			// Removing user from a U- group
			$this->logger->debug('Removing user from a workspace, removing it from all the workspace subgroups too.');
			$users = (array)$space['users'];
			foreach ($users[$NCUser->getUID()]['groups'] as $groupId) {
				$NCGroup = $this->groupManager->get($groupId);
				$NCGroup->removeUser($NCUser);
				$groups[] = $NCGroup->getGID();
				$this->logger->debug('User removed from group: ' . $NCGroup->getDisplayName());
				if ($groupId === GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . $space['id']) {
					$this->logger->debug('Removing user from a workspace manager group, removing it from the WorkspacesManagers group if needed.');
					$this->userService->removeGEFromWM($NCUser, $space['id']);
				}
			}
		} else {
			// Removing user from a regular subgroup or a GE- group
			$groups[] = $gid;
			$NCGroup->removeUser($NCUser);
			$this->logger->debug('User removed from group: ' . $NCGroup->getDisplayName());
			if ($gid === GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . $space['id']) {
				// Removing user from a GE- group
				$this->logger->debug('Removing user from a workspace manager group, removing it from the WorkspacesManagers group if needed.');
				$this->userService->removeGEFromWM($NCUser, $space['id']);
			}
		}

		return new JSONResponse([
			'statuscode' => Http::STATUS_NO_CONTENT,
			'user' => $NCUser->getUID(),
			'groups' => $groups
		]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @GeneralManagerRequired
	 * @param string|object $groupfolder
	 *
	 */
	public function transferUsersToGroups(string $spaceId, $groupfolder) {
		if (gettype($groupfolder) === 'string') {
			$groupfolder = json_decode($groupfolder, true);
		}

		$groupsName = array_keys($groupfolder['groups']);

		$groups = GroupFormatter::formatGroups(
			array_merge(
				[
					$this->groupsWorkspace->getWorkspaceManagerGroup($spaceId),
					$this->groupsWorkspace->getUserGroup($spaceId)
				],
				array_map(function ($groupName) {
					return $this->groupManager->get($groupName);
				}, $groupsName)
			)
		);

		$groupsNameFromAdvancedPermissions = GroupFolderManage::filterGroup($groupfolder);

		$allUsers = $this->userWorkspace->getUsersFromGroup($groupsName);
		$usersFromAdvancedPermissions = $this->userWorkspace->getUsersFromGroup($groupsNameFromAdvancedPermissions);

		$this->groupsWorkspace
			->transferUsersToGroup($allUsers, $this->groupsWorkspace->getUserGroup($spaceId));
		$this->groupsWorkspace
			->transferUsersToGroup($usersFromAdvancedPermissions, $this->groupsWorkspace->getWorkspaceManagerGroup($spaceId));
		$this->groupsWorkspace
			->transferUsersToGroup($usersFromAdvancedPermissions, $this->groupManager->get(ManagersWorkspace::WORKSPACES_MANAGERS));

		$users = $this->userFormatter->formatUsers($allUsers, $groupfolder, $spaceId);

		return new JSONResponse([
			'groups' => $groups,
			'users' => (object)$users
		], Http::STATUS_OK);
	}
}
