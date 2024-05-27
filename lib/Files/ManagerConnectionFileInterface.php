<?php

namespace OCA\Workspace\Files;

interface ManagerConnectionFileInterface {
	public function open(?string $path = null);
	public function close();
}
