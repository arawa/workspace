<?php

namespace OCA\Workspace\Files\Csv;

use OCA\Workspace\Files\FileInterface;

class SeparatorDetector {
	private const SIZE = 1000;
	
	public static function isComma(FileInterface $file): bool {
		$handle = $file->open();
		
		$firstLine = fread($handle, self::SIZE);

		$file->close();

		return strpos($firstLine, Separator::COMMA) !== false;
	}
}
