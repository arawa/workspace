<?php

namespace OCA\Workspace\Service;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\GroupfolderService;
use OCA\Workspace\Service\UserService;
use OCP\IGroupManager;
use OCP\ILogger;

class WorkspaceService {

	/** @var GroupfolderService */
	private $groupfolderService;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ILogger */
	private $logger;

	/** @var SpaceMapper  */
	private $spaceMapper;

	/** @var UserService */
	private $userService;

	public function __construct(
		GroupfolderService $groupfolderService,
		IGroupManager $groupManager,
		ILogger $logger,
		SpaceMapper $spaceMapper
	)
	{
		$this->groupfolderService = $groupfolderService;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->spaceMapper = $spaceMapper;
	}

    	/*
	 * Get a single workspace
	 */
	public function get($id){

		// Gets space info
		$workspace = $this->spaceMapper->find($id)->jsonSerialize();

		// Adds groupfolder's info
		$groupfolder = $this->groupfolderService->get($workspace['groupfolder_id']);
		$workspace['groups'] = $groupfolder['groups'];
		$workspace['quota'] = $groupfolder['quota'];
		$workspace['size'] = $groupfolder['size'];
		$workspace['acl'] = $groupfolder['acl'];

		// Adds users' info 
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
				$users[$user->getUID()] = $this->userService->formatUser($user, $worksspace, 'admin');
			};
		}
		$workspace['users'] = (object) $users;
	
		// Adds groups' info
		$groups = array();
		foreach (array_keys($workspace['groups']) as $gid) {
			$NCGroup = $this->groupManager->get($gid);
			$groups[$gid] = array(
				'gid' => $NCGroup->getGID(),
				'displayName' => $NCGroup->getDisplayName()
			);
		}
	    	$workspace['groups'] = $groups;

		// Returns workspace
		return $workspace;
	}

	/*
	 * Gets all workspaces
	 */
	public function getAll() {

		$spaces = $this->spaceMapper->findAll();
		$workspaces = array();
		foreach ($spaces as $space) {
			$workspaces[] = $this->get($space->getSpaceId());
		}

		return $workspaces;
	}

}
