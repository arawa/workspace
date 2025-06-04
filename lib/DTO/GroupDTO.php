<?php

namespace OCA\Workspace\DTO;

use OCP\IGroup;
use OCA\Workspace\Service\Slugger;
use OCA\Workspace\Service\Group\UserGroup;

class GroupDTO {
	public readonly string $slug;
	public readonly string $gid;
	public readonly string $displayName;
	public readonly array $types;
	private ?int $usersCount;
	
	public function __construct(
		private IGroup $group,
	)
	{
		$this->gid = $group->getGID();
		$this->displayName = $group->getDisplayName();
		$this->types = $group->getBackendNames();
		$this->slug = Slugger::slugger($group->getGID());
		$this->usersCount = null;
	}

	public function countUsers(): self {
		if (!UserGroup::isWorkspaceGroup($this->group)) {
			$users = $this->group->getUsers();
			$users = array_filter($users, fn ($user) => $user->isEnabled());
			$this->usersCount = count($users);
		} else {
			$this->usersCount = $this->group->count();
		}

		return $this;
	}

	public function toArray(): array {
		return [
			'gid' => $this->gid,
			'display_name' => $this->displayName,
			'types' => $this->types,
			'users_count' => $this->usersCount,
			'slug' => $this->slug
		];
	}
}
