<?php

namespace OCA\Workspace\DTO;

use OCP\IGroup;
use OCA\Workspace\Db\Space;

class WorkspaceDTO {
	public readonly int $id;
	public ?string $mountPoint = null;
	/** @var array<OCA\Workspace\DTO\Group> */
	public ?array $groups = null;
	public ?int $quota = null;
	public ?int $size = null;
	public ?bool $acl = null;
	public ?array $manage = null;
	public readonly int $folderId;
	public ?string $name = null;
	public ?string $colorCode = null;
	public ?int $userCount = null;
	/** @var array<OCA\Workspace\DTO\User> */
	public ?array $users = null;
	/** @var array<OCA\Workspace\DTO\Group> */
	public ?array $addedGroups = null;

	public function __construct(Space $space, GroupfolderDTO $groupfolder)
	{
		$this->acl = $groupfolder->acl;
		$this->colorCode = $space->getColorCode();
		$this->folderId = $groupfolder->folderId;
		$this->id = $space->getSpaceId();
		$this->manage = $groupfolder->manage;
		$this->mountPoint = $groupfolder->mountPoint;
		$this->name = $space->getSpaceName();
		$this->quota = $groupfolder->quota;
		$this->size = $groupfolder->quota;
	}

	public function addUser(UserDTO $user): self {
		$this->users[] = $user;
		return $this;
	}

	public function addAddedGroup(GroupDTO $addedGroup): self {
		$this->addedGroups[] = $addedGroup;
		return $this;
	}

	public function addGroup(GroupDTO $group): self {
		$this->groups[] = $group;
		return $this;
	}

	public function setUsersCount(IGroup $group): self {
		$this->userCount = $group->count();
		return $this;
	}

	public function toArray(): array {

		$groups = $this->groups ? array_map(fn ($group) => $group->toArray(), $this->groups) : null;
		$addedGroups = $this->addedGroups ? array_map(fn ($group) => $group->toArray(), $this->addedGroups) : null;
		$users = $this->users ? array_map(fn ($user) => $user->toArray(), $this->users) : null;
		
		return [
			'id' => $this->id,
			'mount_point' => $this->mountPoint,
			'name' => $this->name,
			'groups' => $groups,
			'added_groups' => $addedGroups,
			'quota' => $this->quota,
			'size' => $this->size,
			'acl' => $this->acl,
			'manage' => $this->manage,
			'folder_id' => $this->folderId,
			'color_code' => $this->colorCode,
			'user_count' => $this->userCount,
			'users' => $users,
		];
	}
}
