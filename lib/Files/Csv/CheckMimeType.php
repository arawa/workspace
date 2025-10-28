<?php

namespace OCA\Workspace\Files\Csv;

use OCP\Files\Node;

class CheckMimeType {
	private const SUPPORTED_MIME_TYPES = [
		'application/csv',
		'text/csv',
		'text/plain',
		'inode/x-empty',
	];

	public function checkOnNode(Node $file): bool {
		return in_array($file->getMimetype(), self::SUPPORTED_MIME_TYPES);
	}

	/**
	 * @param array $file as $_FILE
	 */
	public function checkOnArray(array $file): bool {
		if (in_array($file['type'], self::SUPPORTED_MIME_TYPES)) {
			return true;
		}
		$mime = mime_content_type($file['tmp_name']);
		return in_array($mime, self::SUPPORTED_MIME_TYPES);
	}
}
