<?php

namespace OCA\Workspace\Files;

use OCA\Workspace\Files\BasicStreamInterface;

interface FileInterface extends BasicStreamInterface {
	public function getPath(): string;
}
