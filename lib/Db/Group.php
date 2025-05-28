<?php

namespace OCA\Workspace\Db;

use JsonSerializable;

class Group implements JsonSerializable {

	private string $gid;
	private string $displayName;
	/** @var array<String> */
	private array $types;
	private int $usersCount;
	private string $slug;

	public function __construct() {
	}

	public function setGid(string $gid): void {
		$this->gid = $gid;
	}

	public function getGid(): string {
		return $this->gid;
	}

	public function setDisplayName(string $displayName): void {
		$this->displayName = $displayName;
	}

	public function setTypes(array $types): void {
		$this->types = $types;
	}

	public function setUsersCount(int $usersCount): void {
		$this->usersCount = $usersCount;
	}

	public function getUsersCount(): int {
		return $this->usersCount;
	}

	public function setSlug(string $slug): void {
		$this->slug = $slug;
	}
	
	public function jsonSerialize(): array {
		return [
			'gid' => $this->gid,
			'displayName' => $this->displayName,
			'types' => $this->types,
			'usersCount' => $this->usersCount,
			'slug' => $this->slug
		];
	}
}
