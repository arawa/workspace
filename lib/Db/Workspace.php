<?php

namespace OCA\Workspace\Db;

use JsonSerializable;

class Workspace implements JsonSerializable {

	private int $id;
	private ?string $mountPoint = null;
	/** @var array<OCA\Workspace\Db\Group> */
	private ?array $groups = null;
	private ?int $quota = null;
	private ?int $size = null;
	private ?bool $acl = null;
	private ?array $manage = null;
	private int $folderId;
	private ?string $name = null;
	private ?string $colorCode = null;
	private ?int $userCount = null;
	/** @var array<OCA\Workspace\Db\User> */
	private ?array $users = null;
	private ?array $addedGroups = null;
	
	public function __construct() {
	}

	public function setName(string $name): void {
		$this->name = $name;

	}

	public function setId(int $id): void {
		$this->id = $id;
	}

	public function setGroups(array $groups): void {
		$this->groups = $groups;
	}

	public function addGroup(Group $group): void {
		$this->groups[] = $group;
	}

	public function setQuota(?int $quota): void {
		$this->quota = $quota;
	}

	public function getQuota(): ?int {
		return $this->quota;
	}

	public function setSize(int $size): void {
		$this->size = $size;
	}

	public function setAcl(bool $acl): void {
		$this->acl = $acl;
	}

	public function setManage(array $manage): void {
		$this->manage = $manage;
	}

	public function setMountPoint(string $mountPoint): void {
		$this->mountPoint = $mountPoint;
	}

	public function setFolderId(int $folderId): void {
		$this->folderId = $folderId;
	}

	public function setColorCode(string $colorCode): void {
		$this->colorCode = $colorCode;
	}

	public function setUserCount(int $userCount): void {
		$this->userCount = $userCount;
	}

	public function setUsers(array $users): void {
		$this->users = $users;
	}

	public function addUser(User $user): void {
		$this->users[] = $user;
	}

	public function setAddedGroups(array $addedGroups): void {
		$this->addedGroups = $addedGroups;
	}

	public function addAddedGroup(Group $group): void {
		$this->addedGroups[] = $group;
	}

	public function jsonSerialize(): mixed {
		$users = null;
		if (!is_null($this->users)) {
			$users = [];
			foreach ($this->users as $user) {
				$users[$user->getUid()] = $user;
			}
		}

		$groups = null;
		if (!is_null($this->groups)) {
			$groups = [];
			foreach ($this->groups as $group) {
				$groups[$group->getGid()] = $group;
			}
		}

		$addedGroups = null;
		if (!is_null($this->addedGroups)) {
			$addedGroups = [];
			foreach ($this->addedGroups as $group) {
				$addedGroups[$group->getGid()] = $group;
			}
		}
		
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
