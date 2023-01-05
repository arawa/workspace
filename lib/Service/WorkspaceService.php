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
use OCA\Workspace\GroupsWorkspace;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;

class WorkspaceService {
	private IGroupManager $groupManager;
	private ILogger $logger;
	private IManager $shareManager;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private SpaceMapper $spaceMapper;
	private UserService $userService;

	public function __construct(
		IGroupManager $groupManager,
		ILogger $logger,
		IManager $shareManager,
		IUserManager $userManager,
		IUserSession $userSession,
		SpaceMapper $spaceMapper,
		UserService $userService
	) {
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->shareManager = $shareManager;
		$this->spaceMapper = $spaceMapper;
		$this->userManager = $userManager;
		$this->userService = $userService;
		$this->userSession = $userSession;
	}

	/**
	 * @param string $term
	 * @return OCP\IUser[]
	 */
	private function searchUsersByMailing($term) {
		return $this->userManager->getByEmail($term);
	}

	/**
	 * @param string $term
	 * @return OCP\IUser[]
	 */
	private function searchUsersByDisplayName($term) {
		$users = [];

		$term = $term === '*' ? '' : $term;
		$users = $this->userManager->searchDisplayName($term, 50);

		return $users;
	}

	/**
	 * @param string $term
	 * @return OCP\IUser[]
	 */
	private function searchUsers($term) {
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
	private function getUsersFromGroupsOnly($users) {
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
	 * @param array|object $space
	 *
	 * @return array
	 */
	public function autoComplete(string $term, array $space) {
		// lookup users
		$searchingUsers = $this->searchUsers($term);

		$users = [];
		foreach ($searchingUsers as $user) {
			if ($user->isEnabled()) {
				$users[] = $user;
			}
		}

		if ($this->shareManager->shareWithGroupMembersOnly()) {
			$users = $this->getUsersFromGroupsOnly($users);
		}

		// transform in a format suitable for the app
		$data = [];
		foreach ($users as $user) {
			$role = 'user';
			if ($this->groupManager->isInGroup(
				$user->getUID(),
				GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . $space['id'])
			) {
				$role = 'admin';
			}
			$data[] = $this->userService->formatUser($user, $space, $role);
		}

		return $data;
	}

	/*
	 * Gets all workspaces
	 */
	public function getAll() {
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
	 * @param array The workspace to which we want to add users info
	 *
	 */
	public function addUsersInfo($workspace) {
		// Caution: It is important to add users from the workspace's user group before adding the users
		// from the workspace's manager group, as users may be members of both groups
		$this->logger->debug('Adding users information to workspace');
		$users = array();
		$group = $this->groupManager->get(GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_USERS . $workspace['id']);
		// TODO Handle is_null($group) better (remove workspace from list?)
		if (!is_null($group)) {
			foreach ($group->getUsers() as $user) {
				$users[$user->getUID()] = $this->userService->formatUser($user, $workspace, 'user');
			};
		}
		// TODO Handle is_null($group) better (remove workspace from list?)
		$group = $this->groupManager->get(GroupsWorkspace::GID_SPACE . GroupsWorkspace::SPACE_MANAGER . $workspace['id']);
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
	 * @param array The workspace to which we want to add groups info
	 * @return object|array assoc $workspace
	 *
	 */
	public function addGroupsInfo($workspace) {
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
