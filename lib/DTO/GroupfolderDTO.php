<?php

namespace OCA\Workspace\DTO;

class GroupfolderDTO {
	public readonly int $folderId;
	public readonly string $mountPoint;
	public readonly array $groups;
	public readonly int|string $quota;
	public readonly int $size;
	public readonly bool $acl;
	public readonly array $manage;
	
	public function __construct(
		private array $groupfolder
	)
	{
		$this->folderId = $this->groupfolder['id'];
		$this->mountPoint = $this->groupfolder['mount_point'];
		$this->groups = $this->groupfolder['groups'];
		$this->quota = $this->groupfolder['quota'];
		$this->size = $this->groupfolder['size'];
		$this->acl = $this->groupfolder['acl'];
		$this->manage = $this->groupfolder['manage'];
	}

	public function toArray(): array {
		$this->groupfolder['folder_id'] = $this->groupfolder['id'];

		// prevent errors with the "id" from a workspace
		unset($this->groupfolder['id']);

		return $this->groupfolder;
	}
}
