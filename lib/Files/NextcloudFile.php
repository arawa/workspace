<?php

namespace OCA\Workspace\Files;

use OCP\Files\Node;

class NextcloudFile implements FileInterface {
	private $resource;

    private string|false $lineEnding;
		
	public function __construct(private Node $file) {
	}

	/**
	 * @return resource|false
	 * @throws \Exception
	 */
	public function open(?string $path = null) {
        $this->lineEnding = ini_get("auto_detect_line_endings");
        ini_set("auto_detect_line_endings", true);
		$store = $this->file->getStorage();
		$this->resource = $store->fopen($this->file->getInternalPath(), "r");

		if (!$this->resource) {
			throw new \Exception('Something went wrong. Couldn\'t open the file.');
		}

		return $this->resource;
	}

	public function close(): bool {
        ini_set("auto_detect_line_endings", $this->lineEnding);
		return fclose($this->resource);
	}

	public function getPath(): string {
		return $this->file->getInternalPath();
	}

	public function getSize(): false|int|float
	{
		$store = $this->file->getStorage();
		return $store->filesize($this->file->getInternalPath());
	}
}
