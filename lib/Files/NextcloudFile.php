<?php

namespace OCA\Workspace\Files;

use OCP\Files\Storage\IStorage;

class NextcloudFile implements FileInterface {
	private $resource;
		
	public function __construct(private string $path, private IStorage $store) {
	}

	/**
	 * @return resource|false
	 * @throws \Exception
	 */
	public function open(?string $path = null) {
		$this->resource = $this->store->fopen($this->path, "r");

		if (!$this->resource) {
			throw new \Exception('Something went wrong. Couldn\'t open the file.');
		}

		return $this->resource;
	}

	public function close(): bool {
		return fclose($this->resource);
	}

	public function getPath(): string {
		return $this->path;
	}
}
