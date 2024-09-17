<?php

namespace OCA\Workspace\Files;

interface BasicStreamInterface {
	public function open(?string $path = null);
	public function close();
}
