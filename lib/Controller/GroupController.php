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

use OCA\Workspace\Service\Group\GroupFolder\GroupFolderManage;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\GroupsWorkspaceService;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Service\User\UserWorkspace;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class GroupController extends Controller {
	private const DEFAULT = [
		'gid' => null,
		'displayName' => null,
	];

	public function __construct(
		private GroupsWorkspaceService $groupsWorkspace,
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
		private IUserManager $userManager,
		private UserFormatter $userFormatter,
		private UserService $userService,
		private UserWorkspace $userWorkspace
	) {
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Creates a group
	 * NB: This function could probably be abused by space managers to create arbitrary group. But, do we really care?
	 *
	 * @var array $data [
	 *      "gid" => 'Space01',
	 *      "displayName" => 'Space01'
	 * ]
	 * @var string $spaceId for Middleware
	 *
	 */
	public function create(array $data = []): JSONResponse {

		$data = array_merge(self::DEFAULT, $data);

		if (!is_null($this->groupManager->get($data['gid']))) {
			return new JSONResponse(['Group ' . $data['gid'] . ' already exists'], Http::STATUS_FORBIDDEN);
		}

		// Creates group
		$NCGroup = $this->groupManager->createGroup($data['gid']);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Could not create group ' . $data['gid']], Http::STATUS_FORBIDDEN);
		}

		if (!is_null($data['displayName'])) {
			$NCGroup->setDisplayName($data['displayName']);
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
	 * @var int $spaceId
	 *
	 */
	public function delete(string $gid, int $spaceId): JSONResponse {
		// TODO Use groupfolder api to retrieve workspace group.
		if (substr($gid, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(['You may only delete workspace groups of this space (ie: group\'s name does not end by the workspace\'s ID)'], Http::STATUS_FORBIDDEN);
		}

		// Delete group
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Group ' . $gid . ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
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
	 * @var int $spaceId
	 *
	 */
	public function rename(string $newGroupName,
		string $gid,
		int $spaceId): JSONResponse {
		$groups = $this->groupManager->search($newGroupName);
		$groups = array_filter($groups, function ($group) {
			return str_starts_with($group->getGID(), 'SPACE-GE-')
				|| str_starts_with($group->getGID(), 'SPACE-U-')
				|| str_starts_with($group->getGID(), 'SPACE-G-');
		});

		$groupsNameSearched = array_map(
			fn ($group) => $group->getGID(),
			$groups);

		if (!empty($groups)
			&& in_array($newGroupName, $groupsNameSearched)) {
			return new JSONResponse(
				'This group already exists. Please, change the name',
				Http::STATUS_CONFLICT
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
	 * Adds a user to a group.
	 * The function automaticaly adds the user the the corresponding workspace's user group, and to the application
	 * manager group when we are adding a workspace manager
	 *
	 * @var mixed $workspace
	 * @var string $gid
	 * @var string $user
	 *
	 */
	public function addUser(string $spaceId, string $gid, string $user): JSONResponse {
		// Makes sure group exist
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			// In some cases, frontend might give a group's displayName rather than its gid
			$NCGroup = $this->groupManager->search($gid);
			if (empty($NCGroup)) {
				return new JSONResponse(['Group ' . $gid . ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
			}
			$NCGroup = $NCGroup[0];
		}

		// Adds user to group
		$NCUser = $this->userManager->get($user);
		$NCGroup->addUser($NCUser);

		// Adds the user to the application manager group when we are adding a workspace manager
		if ($gid === WorkspaceManagerGroup::get($spaceId)) {
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
		$UGroup = $this->groupManager->get(UserGroup::get($spaceId));
		$UGroup->addUser($NCUser);

		return new JSONResponse(['message' => 'The user ' . $user . ' is added in the ' . $gid . ' group'], Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Removes a user from a group
	 * The function also remove the user from all workspace 'subgroup when the user is being removed from the U- group
	 * and from the WorkspacesManagers group when the user is being removed from the GE- group
	 *
	 * @param array|string $space
	 * @var string $gid
	 * @var string $user
	 *
	 */
	public function removeUser(array|string $space,
		string $gid,
		string $user): JSONResponse {
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
		if ($gid === WorkspaceManagerGroup::get($space['id'])
		|| $gid === UserGroup::get($space['id'])) {
			// Removing user from a U- group
			$this->logger->debug('Removing user from a workspace, removing it from all the workspace subgroups too.');
			$users = (array)$space['users'];
			foreach ($users[$NCUser->getUID()]['groups'] as $groupId) {
				$NCGroup = $this->groupManager->get($groupId);
				$NCGroup->removeUser($NCUser);
				$groups[] = $NCGroup->getGID();
				$this->logger->debug('User removed from group: ' . $NCGroup->getDisplayName());
				if ($groupId === WorkspaceManagerGroup::get($space['id'])) {
					$this->logger->debug('Removing user from a workspace manager group, removing it from the WorkspacesManagers group if needed.');
					if ($this->userService->canRemoveWorkspaceManagers($NCUser, $space)) {
						$this->userService->removeGEFromWM($NCUser);
					}
				}
			}
		} else {
			// Removing user from a regular subgroup or a GE- group
			$groups[] = $gid;
			$NCGroup->removeUser($NCUser);
			$this->logger->debug('User removed from group: ' . $NCGroup->getDisplayName());
			if ($gid === WorkspaceManagerGroup::get($space['id'])) {
				// Removing user from a GE- group
				$this->logger->debug('Removing user from a workspace manager group, removing it from the WorkspacesManagers group if needed.');
				if ($this->userService->canRemoveWorkspaceManagers($NCUser, $space)) {
					$this->userService->removeGEFromWM($NCUser);
				}
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
	 * @GeneralManagerRequired
	 * @param string|array $groupfolder
	 *
	 */
	public function transferUsersToGroups(string $spaceId,
		string|array $groupfolder): JSONResponse {
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

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function search(string $pattern): JSONResponse {
        
        $groups = $this->groupManager->search($pattern);

        $groupsFormatted = GroupFormatter::formatGroups($groups);
        
        return new JSONResponse($groupsFormatted);
    }
}
