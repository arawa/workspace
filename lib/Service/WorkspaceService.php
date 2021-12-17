<?php

namespace OCA\Workspace\Service;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\UserService;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserManager;

class WorkspaceService {

	/** @var IGroupManager */
	private $groupManager;

	/** @var ILogger */
	private $logger;

	/** @var IUserManager */
	private $userManager;

	/** @var SpaceMapper  */
	private $spaceMapper;

	/** @var UserService */
	private $userService;

	public function __construct(
		IGroupManager $groupManager,
		ILogger $logger,
		IUserManager $userManager,
		SpaceMapper $spaceMapper,
		UserService $userService
	)
	{
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->spaceMapper = $spaceMapper;
		$this->userManager = $userManager;
		$this->userService = $userService;
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
		$term = $term === '*' ? '' : $term;
		$searchingUsers = $this->userManager->searchDisplayName($term, 50);

		$users = [];
		foreach($searchingUsers as $user) {
			if($user->isEnabled()) {
					$users[] = $user;
				}
		}

		// transform in a format suitable for the app
		$data = [];
		foreach($users as $user) {
			$role = 'user';
			if ($this->groupManager->isInGroup(
					$user->getUID(),
					Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $space['id'])
				) {
				$role = 'admin';
			}
			$data[] = $this->userService->formatUser($user, $space, $role);
		}

		// return info
		return $data;
	}

	/*
	 * Gets all workspaces
	 */
	public function getAll() {

		// Gets all spaces
		$spaces = $this->spaceMapper->findAll();
		$newSpaces = [];
		foreach($spaces as $space) {
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
		$group = $this->groupManager->get(Application::GID_SPACE . Application::ESPACE_USERS_01 . $workspace['id']);
		// TODO Handle is_null($group) better (remove workspace from list?)
		if (!is_null($group)) {
			foreach($group->getUsers() as $user) {
				$users[$user->getUID()] = $this->userService->formatUser($user, $workspace, 'user');
			};
		}
		// TODO Handle is_null($group) better (remove workspace from list?)
		$group = $this->groupManager->get(Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $workspace['id']);
		if (!is_null($group)) {
			foreach($group->getUsers() as $user) {
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
