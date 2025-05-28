<?php

namespace OCA\Workspace\Factory;

use OCA\Workspace\Db\User;
use OCA\Workspace\DTO\WorkspaceDTO;
use OCA\Workspace\DTO\WorkspaceGroupsDTO;
use OCA\Workspace\Roles;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupsWorkspaceService;
use OCP\IURLGenerator;
use OCP\IUser;

class UserFactory {
	public function __construct(
		private IURLGenerator $urlGenerator,
		private ConnectedGroupsService $connectedGroupsService,
		private GroupsWorkspaceService $groupsWorkspace,
	) {
	}

	public function createUser(IUser $ncUser, WorkspaceGroupsDTO $workspaceGroupsDTO, WorkspaceDTO $workspaceDTO): User {
		$user = new User();
		$user->setUid($ncUser->getUID());
		$user->setDisplayName($ncUser->getDisplayName());
		$user->setEmail($ncUser->getEMailAddress());
		$user->setSubtitle($ncUser->getEMailAddress());
		$user->setProfile($this->urlGenerator->linkToRouteAbsolute('core.ProfilePage.index', ['targetUserId' => $user->getUID()]));

		if ($workspaceGroupsDTO->manager->inGroup($ncUser)) {
			$user->setRole(Roles::Admin);
		} else {
			$user->setRole(Roles::User);
		}
		
		$user->setIsConnected(!$this->connectedGroupsService->isStrictSpaceUser($user->getUID(), $workspaceGroupsDTO->user->getGID()));
		$user->setGroups($this->groupsWorkspace->getGroupsUserFromGroupfolder($ncUser, $workspaceDTO->toArray(), $workspaceDTO->spaceId));

		return $user;
	}
}
