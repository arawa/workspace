<?php

namespace OCA\Workspace\Files;

class FileUploader implements FileInterface {
	private $resource;

    private string|false $lineEnding;

	public function __construct(private string $path) {
	}

	/**
	 * @return resource|false
	 * @throws \Exception
	 */
	public function open(?string $path = null) {
        $this->lineEnding = ini_get("auto_detect_line_endings");
        ini_set("auto_detect_line_endings", true);
		$this->resource = fopen($this->path, "r");

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
		return $this->path;
	}

	public function getSize(): false|int|float
	{
		return filesize($this->path);
	}
}
