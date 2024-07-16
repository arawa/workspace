<?php

namespace OCA\Workspace\Files;

interface FileInterface extends BasicStreamInterface {
	public function getPath(): string;
}
