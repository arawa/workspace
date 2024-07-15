<?php

namespace OCA\Workspace\Files;

class FileUploader implements FileInterface {
	private $resource;

	public function __construct(private string $path) {
	}

	/**
	 * @return resource|false
	 * @throws \Exception
	 */
	public function open(?string $path = null) {
		$this->resource = fopen($this->path, "r");

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
