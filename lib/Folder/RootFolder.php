<?php

namespace OCA\Workspace\Folder;

use OCP\Files\IRootFolder;

class RootFolder {
	public function __construct(
		private IRootFolder $rootFolder
	) {
	}

	public function getRootFolderStorageId(): ?int {
		return $this->rootFolder->getMountPoint()->getNumericStorageId();
	}
}
