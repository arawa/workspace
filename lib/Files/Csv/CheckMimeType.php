<?php

namespace OCA\Workspace\Files\Csv;

use OCP\Files\Node;

class CheckMimeType {
	private const MIME_TYPE = 'text/csv';

	public function checkOnNode(Node $file): bool {
		return $file->getMimetype() !== self::MIME_TYPE;
	}

	/**
	 * @param array $file as $_FILE
	 */
	public function checkOnArray(array $file): bool {
		return $file['type'] !== self::MIME_TYPE;
	}
}
