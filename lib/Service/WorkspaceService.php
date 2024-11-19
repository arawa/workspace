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
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Share\Group\GroupMembersOnlyChecker;
use OCA\Workspace\Share\Group\ShareMembersOnlyFilter;
use OCP\IGroupManager;
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
		private UserService $userService,
		private ShareMembersOnlyFilter $shareMembersFilter,
		private GroupMembersOnlyChecker $memberGroupOnlyChecker,
		private ConnectedGroupsService $connectedGroups,
	) {
	}

	/**
	 * @param string $term
	 * @return IUser[]
	 * @deprecated since 3.0.1
	 * @uses OCA\Workspace\User\UserSearcher
	 */
	private function searchUsersByMailing(string $term): array {
		return $this->userManager->getByEmail($term);
	}

	/**
	 * @param string $term
	 * @return IUser[]
	 * @deprecated since 3.0.1
	 * @uses OCA\Workspace\User\UserSearcher
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
	 * @return IUser[]
	 * @deprecated since 3.0.1
	 * @uses OCA\Workspace\User\UserSearcher
	 */
	public function searchUsers(string $term): array {
		$users = [];
		$REGEX_FULL_MAIL = '/^[a-zA-Z0-9_.+-].+@[a-zA-Z0-9_.+-]/';

		if (preg_match($REGEX_FULL_MAIL, $term) === 1) {
			$users = $this->searchUsersByMailing($term);
		} else {
			$users = $this->searchUsersByDisplayName($term);
		}

		/**
		 * Change OC\User\LazyUser to OC\User\User.
		 */
		$users = array_map(fn ($user) => $this->userManager->get($user->getUID()), $users);

		return $users;
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

		if ($this->memberGroupOnlyChecker->checkboxIsChecked()) {
			$users = $this->shareMembersFilter->filterUsersGroupOnly($users);
		}

		if ($this->memberGroupOnlyChecker->groupsExcludeSelected()) {
			if ($this->memberGroupOnlyChecker->checkMemberInGroupExcluded()) {
				$users = $this->shareMembersFilter->excludeGroupsList($users);
			}
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
	 * Adds users information to a workspace
	 */
	public function addUsersInfo(string|array $workspace): \stdClass {
		// Caution: It is important to add users from the workspace's user group before adding the users
		// from the workspace's manager group, as users may be members of both groups
		$this->logger->debug('Adding users information to workspace');
		$users = [];
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
				if (isset($users[$user->getUID()])) {
					$users[$user->getUID()] = $this->userService->formatUser($user, $workspace, 'admin');
				}
			};
		}

		return (object) $users;
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
		if (!isset($workspace['groups'])) {
			return $workspace;
		}
		$groups = array_map(
			fn ($gid) => $this->groupManager->get($gid),
			array_keys($workspace['groups'])
		);
		$addedGroups = [];
		foreach(array_keys($workspace['groups']) as $gid) {
			$addedToGroup = $this->connectedGroups->getConnectedGroupsToSpaceGroup($gid);
			if ($addedToGroup !== null) {
				$addedGroups = array_merge($addedGroups, $addedToGroup);
			}
		}

		$workspace['groups'] = GroupFormatter::formatGroups($groups);
		$workspace['added_groups'] = GroupFormatter::formatGroups($addedGroups);

		return $workspace;
	}
}
