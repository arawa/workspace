<?php

namespace OCA\Workspace\Builder;

use OCA\Workspace\Db\Group;
use OCA\Workspace\Db\User;
use OCA\Workspace\Db\Workspace;

class WorkspaceBuilder {
	private Workspace $workspace;

	public function __construct() {
		$this->workspace = new Workspace();
	}

	public function setId(int $id): self {
		$this->workspace->setId($id);
		return $this;
	}

	public function setName(string $name): self {
		$this->workspace->setName($name);
		return $this;
	}

	public function setMountPoint(string $mountPoint): self {
		$this->workspace->setMountPoint($mountPoint);
		return $this;
	}

	public function setQuota(int $quota): self {
		$this->workspace->setQuota($quota);
		return $this;
	}

	public function setSize(int $size): self {
		$this->workspace->setSize($size);
		return $this;
	}

	public function setAcl(bool $acl): self {
		$this->workspace->setAcl($acl);
		return $this;
	}

	public function setFolderId(int $folderId): self {
		$this->workspace->setFolderId($folderId);
		return $this;
	}

	public function setColorCode(string $colorCode): self {
		$this->workspace->setColorCode($colorCode);
		return $this;
	}

	public function setManage(array $manage): self {
		$this->workspace->setManage($manage);
		return $this;
	}

	public function setUserCount(int $userCount): self {
		$this->workspace->setUserCount($userCount);
		return $this;
	}

	public function addUser(User $user): self {
		$this->workspace->addUser($user);
		return $this;
	}

	public function addGroup(Group $group): self {
		$this->workspace->addGroup($group);
		return $this;
	}

	public function addAddedGroup(Group $group): self {
		$this->workspace->addAddedGroup($group);
		return $this;
	}

	public function build(): Workspace {
		return $this->workspace;
	}
}
