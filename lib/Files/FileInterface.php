<?php

namespace OCA\Workspace\Files;

interface FileInterface extends BasicStreamInterface {
	public function getPath(): string;
	public function getSize(): false|int|float;
}
