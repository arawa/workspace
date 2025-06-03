<?php

namespace OCA\Workspace\DTO;

use OCP\IUser;
use OCP\IURLGenerator;
use OCA\Workspace\Roles;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupsWorkspaceService;

class UserDTO {
	public ?string $uid = null;
	private ?string $name = null;
	private ?string $displayName = null;
	private ?string $email = null;
	private ?string $subtitle = null;
	/** @var array<OCA\Workspace\DTO\GroupDTO> */
	private ?array $groups = null;
	private ?bool $isConnected = null;
	private ?string $profil = null;
	private ?string $role = null;

	public function __construct(
		private IUser $user,
		private WorkspaceGroupsDTO $workspaceGroupsDto,
	)
	{
		$this->uid = $user->getUID();
		$this->name = $user->getDisplayName();
		$this->displayName = $user->getDisplayName();
		$this->email = $user->getEMailAddress();
		$this->subtitle = $user->getEMailAddress();
		$this->role = $workspaceGroupsDto->manager->inGroup($user) ? Roles::Admin : Roles::User;
	}

	public function fillIsConnected(ConnectedGroupsService $connectedGroupService): self {
		$this->isConnected = !$connectedGroupService->isStrictSpaceUser($this->uid, $this->workspaceGroupsDto->user->getGID());
		return $this;
	}

	public function fillProfil(IURLGenerator $urlGenerator): self {
		$this->profil = $urlGenerator->linkToRouteAbsolute('core.ProfilePage.index', ['targetUserId' => $this->uid]);
		return $this;
	}

	public function fillGroups(GroupsWorkspaceService $groupsWorkspace, int $spaceId, GroupfolderDTO $groupfolder): self {
		$this->groups = $groupsWorkspace->getGroupsUserFromGroupfolder($this->user, $groupfolder->toArray(), $spaceId);
		return $this;
	}

	public function toArray(): array {
		return [
			'uid' => $this->uid,
			'display_name' => $this->displayName,
			'role' => $this->role,
			'name' => $this->name,
			'email' => $this->email,
			'subtitle' => $this->subtitle,
			'profil' => $this->profil,
			'groups' => $this->groups,
			'is_connected' => $this->isConnected,
		];
	}
}
