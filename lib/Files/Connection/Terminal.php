<?php

namespace OCA\Workspace\Files\Connection;

use OCA\Workspace\Files\BasicStreamInterface;

class Terminal implements BasicStreamInterface {
	private $stream;

	public function __construct() {
	}

	/**
	 * @return resource|false
	 */
	public function open(?string $path = null) {
		$this->stream = fopen($path, 'r');
		return $this->stream;
	}

	public function close(): bool {
		return fclose($this->stream);
	}
}
