<?php

namespace OCA\Workspace\DTO;

use OCP\IGroup;

class WorkspaceGroupsDTO {
	public function __construct(
		public readonly IGroup $manager,
		public readonly IGroup $user,
	) {
	}
}
