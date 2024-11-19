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
use OCA\Workspace\Service\Slugger;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Service\User\UserWorkspace;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Share\Group\GroupMembersOnlyChecker;
use OCA\Workspace\Share\Group\ShareMembersOnlyFilter;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class GroupController extends Controller {
	private const DEFAULT = [
		'gid' => null,
		'displayName' => null,
	];

	public function __construct(
		private GroupsWorkspaceService $groupsWorkspace,
		private IGroupManager $groupManager,
		private SpaceManager $spaceManager,
		private IUserManager $userManager,
		private ISearch $collaboratorSearch,
		private LoggerInterface $logger,
		private UserFormatter $userFormatter,
		private UserService $userService,
		private UserWorkspace $userWorkspace,
		private GroupMembersOnlyChecker $groupMembersOnlyChecker,
		private ShareMembersOnlyFilter $shareMembersOnlyFilter,
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
	 *            "gid" => 'Space01',
	 *            "displayName" => 'Space01'
	 *            ]
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
				'displayName' => $NCGroup->getDisplayName(),
				'types' => $NCGroup->getBackendNames(),
				'usersCount' => 0,
				'slug' => Slugger::slugger($NCGroup->getGID())
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
	 * Remove a user from a workspace.
	 *
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * @param array|string $space
	 * @param string $gid
	 * @param string $user
	 * @return JSONResponse
	 */
	public function removeUserFromWorkspace(
		array|string $space,
		string $gid,
		string $user,
	): JSONResponse {
		if (gettype($space) === 'string') {
			$space = json_decode($space, true);
		}

		$NcUser = $this->userManager->get($user);

		$gidsStringify = array_keys($space['groups']);

		$gidsStringify = array_filter(
			$gidsStringify,
			fn ($gid) => $this->groupManager->isInGroup($NcUser->getUID(), $gid)
		);

		// Makes sure group exist
		foreach ($gidsStringify as $gid) {
			if (!$this->groupManager->groupExists($gid)) {
				throw new \Exception("The $gid group is not exist");
			}
		}

		$groups = array_map(
			fn ($gid) => $this->groupManager->get($gid),
			$gidsStringify
		);

		if ($this->userService->canRemoveWorkspaceManagers($NcUser)) {
			$this->userService->removeGEFromWM($NcUser);
			$workspacesManagersGroup = $this->groupManager->get('WorkspacesManagers');
			$groupnames[] = $workspacesManagersGroup->getGID();
		}

		foreach ($groups as $group) {
			$group->removeUser($NcUser);
			$groupnames[] = $group->getGID();
		}

		return new JSONResponse([
			'statuscode' => Http::STATUS_NO_CONTENT,
			'user' => $NcUser->getUID(),
			'groups' => $groupnames
		]);
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
	public function removeUser(
		IRequest $request,
		array|string $space,
		string $gid,
		string $user): JSONResponse {
		$cascade = $request->getParam('cascade', false);
		
		$NcUser = $this->userManager->get($user);
		$group = $this->groupManager->get($gid);
	
		if (!$this->groupManager->isInGroup($NcUser->getUID(), $group->getGID())) {
			throw new \Exception("The $NcUser->getUID() user is not present in the $group->getGID() group.");
		}
	
		if ((str_starts_with($group->getGID(), 'SPACE-U'))
			&& !$cascade) {
			throw new \Exception("You must define cascade to true as parameter in the request to remove the user from $group->getGID() group.");
		}
	
		$groupnames = [];

		if (str_starts_with($group->getGID(), 'SPACE-GE')) {
			if ($this->userService->canRemoveWorkspaceManagers($NcUser)) {
				$this->userService->removeGEFromWM($NcUser);
				$workspacesManagersGroup = $this->groupManager->get('WorkspacesManagers');
				$groupnames[] = $workspacesManagersGroup->getGID();
			}
		}

		$group->removeUser($NcUser);
		$groupnames[] = $group->getGID();
	
		if ($cascade) {
			if (gettype($space) === 'string') {
				$space = json_decode($space, true);
			}
	
			$gidsStringify = array_keys($space['groups']);
	
			$gidsStringify = array_filter(
				$gidsStringify,
				fn ($gid) => $this->groupManager->isInGroup($NcUser->getUID(), $gid)
			);
	
			foreach ($gidsStringify as $gid) {
				if (!$this->groupManager->groupExists($gid)) {
					throw new \Exception("The $gid group is not exist");
				}
			}
	
			$groups = array_map(
				fn ($gid) => $this->groupManager->get($gid),
				$gidsStringify
			);
	
			if ($this->userService->canRemoveWorkspaceManagers($NcUser)) {
				$this->userService->removeGEFromWM($NcUser);
				$workspacesManagersGroup = $this->groupManager->get('WorkspacesManagers');
				$groupnames[] = $workspacesManagersGroup->getGID();
			}
	
			foreach ($groups as $group) {
				$group->removeUser($NcUser);
				$groupnames[] = $group->getGID();
			}
		}
	
		return new JSONResponse([
			'statuscode' => Http::STATUS_NO_CONTENT,
			'user' => $NcUser->getUID(),
			'groups' => $groupnames
		]);
	}

	/**
	 * @NoAdminRequired
	 * @GeneralManagerRequired
	 */
	public function attachGroupToSpace(int $spaceId, string $gid) {
		$workspace = $this->spaceManager->get($spaceId);
		$this->spaceManager->attachGroup($workspace['groupfolder_id'], $gid);

		return new JSONResponse([
			'message' => sprintf('The %s group is attached to the %s workspace (i.e groupfolder)', $gid, $workspace['name']),
		], Http::STATUS_ACCEPTED);
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
	 *
	 * @param string $pattern The pattern to search
	 * @param bool $ignoreSpaces (not require) Ignore the workspace groups
	 * @param array<string> $groupsPresents are groups already present
	 */
	public function search(string $pattern, ?bool $ignoreSpaces = null, array $groupsPresents = []): JSONResponse {

		[$groups] = $this->collaboratorSearch->search(
			$pattern,
			[
				IShare::TYPE_GROUP
			],
			false,
			200,
			0
		);
		
		$groupsSearching = array_map(
			fn ($group) => $group['value']['shareWith'],
			$groups['groups']
		);

		$groupsExact = [];
		if (!empty($groups['exact']['groups'])) {
			$groupsExact[] = $groups['exact']['groups'][0]['value']['shareWith'];
		}

		$groups = array_merge($groupsSearching, $groupsExact);

		$groups = array_map(fn ($group) => $this->groupManager->get($group), $groups);

		if (!is_null($ignoreSpaces) && (bool)$ignoreSpaces) {
			$groups = array_filter($groups, function ($group) {
				$gid = $group->getGID();

				return !str_starts_with($gid, WorkspaceManagerGroup::getPrefix())
					&& !str_starts_with($gid, UserGroup::getPrefix())
					&& !str_starts_with($gid, 'SPACE-G')
					&& $gid !== ManagersWorkspace::GENERAL_MANAGER
					&& $gid !== ManagersWorkspace::WORKSPACES_MANAGERS;
			});
		}

		$groups = array_filter($groups, fn ($group) => !in_array($group->getGID(), $groupsPresents));

		$groupsFormatted = GroupFormatter::formatGroups($groups);

		uksort($groupsFormatted, 'strcasecmp');

		return new JSONResponse($groupsFormatted);
	}
}
