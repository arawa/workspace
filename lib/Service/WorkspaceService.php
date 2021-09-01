<?php

namespace OCA\Workspace\Service;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\GroupfolderService;
use OCA\Workspace\Service\UserService;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserManager;

class WorkspaceService {

	/** @var GroupfolderService */
	private $groupfolderService;

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
		GroupfolderService $groupfolderService,
		IGroupManager $groupManager,
		ILogger $logger,
		IUserManager $userManager,
		SpaceMapper $spaceMapper,
		UserService $userService
	)
	{
		$this->groupfolderService = $groupfolderService;
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
	 * @param string $spaceId
	 *
	 * @return array
	 */
	public function autoComplete(string $term, string $spaceId) {
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
		$space = $this->get($spaceId);
		foreach($users as $user) {
			$data[] = $this->userService->formatUser($user, $space, 'user');
		}

		// return info
		return $data;
	}

	/*
	 * Gets a single workspace
	 */
	public function get($id){

		// Gets space info
		$workspace = $this->spaceMapper->find($id)->jsonSerialize();

		// Adds groupfolder's info
		$groupfolder = $this->groupfolderService->get($workspace['groupfolder_id']);
		$this->addGroupfolderInfo($space, $groupfolder);

		// Adds users' info 
		$this->addUsersInfo($workspace);
	
		// Adds groups' info
		$this->addGroupsInfo($workspace);

		// Returns workspace
		return $workspace;
	}

	/*
	 * Gets all workspaces
	 */
	public function getAll() {

		// Gets all spaces
		$spaces = $this->spaceMapper->findAll();

		// Supplement them with additional info
		$workspaces = array();
		$groupfolders = $this->groupfolderService->getAll();
		foreach ($spaces as $space) {
			$workspace = $space->jsonSerialize();
			$this->addGroupfolderInfo($workspace, $groupfolders[$workspace['groupfolder_id']]);
			$this->addUsersInfo($workspace);
			$this->addGroupsInfo($workspace);
			$workspaces[] = $workspace;
		}
		
		return $workspaces;
	}

	/**
	 *
	 * Adds groupfolder information to a workspace
	 *
	 * @param array $workspace The workspace to which we want to add groupfolder info
	 * @param array $groupfolder The groupfolder to retrieve info from
	 *
	 */
	private function addGroupfolderInfo(&$workspace, $groupfolder) {

		$workspace['groups'] = $groupfolder['groups'];
		$workspace['quota'] = $groupfolder['quota'];
		$workspace['size'] = $groupfolder['size'];
		$workspace['acl'] = $groupfolder['acl'];

		return;
	}

	/**
	 *
	 * Adds users information to a workspace
	 *
	 * @param array The workspace to which we want to add users info
	 *
	 */
	private function addUsersInfo(&$workspace) {
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

		return;
	}

	/**
	 *
	 * Adds groups information to a workspace
	 *
	 * @param array The workspace to which we want to add groups info
	 *
	 */
	private function addGroupsInfo(&$workspace) {
		$groups = array();
		foreach (array_keys($workspace['groups']) as $gid) {
			$NCGroup = $this->groupManager->get($gid);
			$groups[$gid] = array(
				'gid' => $NCGroup->getGID(),
				'displayName' => $NCGroup->getDisplayName()
			);
		}
		$workspace['groups'] = $groups;

		return;
	}
}
