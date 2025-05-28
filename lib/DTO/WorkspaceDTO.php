<?php

namespace OCA\Workspace\DTO;

use OCA\Workspace\Db\Space;

class WorkspaceDTO {
	private readonly array $workspace;

	public readonly int $spaceId;

	public function __construct(Space $space, GroupfolderDTO $groupfolder) {
		$this->workspace = array_merge($groupfolder->toArray(), $space->jsonSerialize());

		$this->spaceId = $this->workspace['id'];
	}

	public function toArray(): array {
		return $this->workspace;
	}
}
