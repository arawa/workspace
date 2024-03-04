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

namespace OCA\Workspace\Service;

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;
use Psr\Log\LoggerInterface;

class WorkspaceService {
	public function __construct(
		private IGroupManager $groupManager,
		private IManager $shareManager,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private LoggerInterface $logger,
		private SpaceMapper $spaceMapper,
		private UserService $userService
	) {
	}

	/**
	 * @param string $term
	 * @return OCP\IUser[]
	 */
	private function searchUsersByMailing(string $term): array {
		return $this->userManager->getByEmail($term);
	}

	/**
	 * @param string $term
	 * @return OCP\IUser[]
	 */
	private function searchUsersByDisplayName(string $term): array {
		$users = [];

		$term = $term === '*' ? '' : $term;
		$users = $this->userManager->searchDisplayName($term, 50);

		$users = array_unique($users, SORT_REGULAR);

		return $users;
	}

	/**
	 * @param string $term
	 * @return OCP\IUser[]
	 */
	private function searchUsers(string $term): array {
		$users = [];
		$REGEX_FULL_MAIL = '/^[a-zA-Z0-9_.+-].+@[a-zA-Z0-9_.+-]/';

		if (preg_match($REGEX_FULL_MAIL, $term) === 1) {
			$users = $this->searchUsersByMailing($term);
		} else {
			$users = $this->searchUsersByDisplayName($term);
		}

		return $users;
	}

	/**
	 * @param IUser[] $users
	 * @return IUser[]
	 */
	private function getUsersFromGroupsOnly(array $users): array {
		$usersFromGroups = [];
		$userSession = $this->userSession->getUser();
		$groupsOfUserSession = $this->groupManager->getUserGroups($userSession);
		foreach ($groupsOfUserSession as $group) {
			$usersFromGroups = array_merge($usersFromGroups, $group->getUsers());
		}

		$usersFromGroups = array_filter($usersFromGroups, function ($user) use ($users) {
			return in_array($user, $users);
		});

		return $usersFromGroups;
	}

	/**
	 * Returns a list of users whose name matches $term
	 *
	 * @param string $term
	 * @param array|string $space
	 *
	 * @return array
	 */
	public function autoComplete(string $term, array|string $space): array {
		// lookup users
		$searchingUsers = $this->searchUsers($term);

		$users = [];
		foreach ($searchingUsers as $user) {
			if ($user->isEnabled()) {
				$users[] = $user;
			}
		}

		if ($this->shareManager->shareWithGroupMembersOnly() 
            && !$this->groupManager->isInGroup(
                $this->userSession->getUser()->getUID(),
                ManagersWorkspace::GENERAL_MANAGER)
            ) {
			$users = $this->getUsersFromGroupsOnly($users);
		}

		// transform in a format suitable for the app
		$data = [];
		foreach ($users as $user) {
			$role = 'user';
			if ($this->groupManager->isInGroup(
				$user->getUID(),
				WorkspaceManagerGroup::get($space['id']))
			) {
				$role = 'admin';
			}
			$data[] = $this->userService->formatUser($user, $space, $role);
		}

		return $data;
	}

	/**
	 * @return Space[] - all spaces
	 */
	public function getAll(): array {
		// Gets all spaces
		$spaces = $this->spaceMapper->findAll();
		$newSpaces = [];
		foreach ($spaces as $space) {
			$newSpace = $space->jsonSerialize();
			$newSpaces[] = $newSpace;
		}
		return $newSpaces;
	}

	/**
	 *
	 * Adds users information to a workspace
	 *
	 * @param string|array The workspace to which we want to add users info
	 *
	 */
	public function addUsersInfo(string|array $workspace): array {
		// Caution: It is important to add users from the workspace's user group before adding the users
		// from the workspace's manager group, as users may be members of both groups
		$this->logger->debug('Adding users information to workspace');
		$users = array();
		$group = $this->groupManager->get(UserGroup::get($workspace['id']));
		// TODO Handle is_null($group) better (remove workspace from list?)
		if (!is_null($group)) {
			foreach ($group->getUsers() as $user) {
				$users[$user->getUID()] = $this->userService->formatUser($user, $workspace, 'user');
			};
		}
		// TODO Handle is_null($group) better (remove workspace from list?)
		$group = $this->groupManager->get(WorkspaceManagerGroup::get($workspace['id']));
		if (!is_null($group)) {
			foreach ($group->getUsers() as $user) {
				$users[$user->getUID()] = $this->userService->formatUser($user, $workspace, 'admin');
			};
		}
		$workspace['users'] = (object) $users;

		return $workspace;
	}

	/**
	 *
	 * Adds groups information to a workspace
	 *
	 * @param array|string The workspace to which we want to add groups info
	 * @return array assoc $workspace
	 *
	 */
	public function addGroupsInfo(array|string $workspace): array {
		$groups = array();
		foreach (array_keys($workspace['groups']) as $gid) {
			$NCGroup = $this->groupManager->get($gid);
			$groups[$gid] = array(
				'gid' => $NCGroup->getGID(),
				'displayName' => $NCGroup->getDisplayName()
			);
		}
		$workspace['groups'] = $groups;

		return $workspace;
	}
}
