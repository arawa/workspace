<?php

namespace OCA\Workspace\Db;

use JsonSerializable;

class User implements JsonSerializable {
	private ?string $uid = null;
	private ?string $name = null;
	private ?string $displayName = null;
	private ?string $email = null;
	private ?string $subtitle = null;
	/** @var array<OCA\Workspace\Db\Group> */
	private ?array $groups = null;
	private ?bool $isConnected = null;
	private ?string $profil = null;
	private ?string $role = null;

	public function __construct() {
	}

	public function getUid(): ?string {
		return $this->uid;
	}
	
	public function setUid(?string $uid): void {
		$this->uid = $uid;
	}
	
	public function setName(?string $name): void {
		$this->name = $name;
	}

	public function setDisplayName(?string $displayName): void {
		$this->displayName = $displayName;
	}

	public function setEmail(?string $email): void {
		$this->email = $email;
	}

	public function setSubtitle(?string $subtitle): void {
		$this->subtitle = $subtitle;
	}

	public function setGroups(array $groups): void {
		$this->groups = $groups;
	}

	public function addGroup(Group $group): void {
		$this->groups[] = $group;
	}

	public function setIsConnected(bool $isConnected): void {
		$this->isConnected = $isConnected;
	}

	public function setProfile(?string $profil): void {
		$this->profil = $profil;
	}

	public function setRole(?string $role): void {
		$this->role = $role;
	}

	public function jsonSerialize(): mixed {
		return [
			'uid' => $this->uid,
			'name' => $this->displayName,
			'email' => $this->email,
			'subtitle' => $this->email,
			'groups' => $this->groups,
			'is_connected' => $this->isConnected,
			'profile' => $this->profil,
			'role' => $this->role
		];
	}
}
