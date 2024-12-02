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

	public static function isCommaForAllFile(FileInterface $file): bool {
		$handle = $file->open();
		$lines = fread($handle, $file->getSize());
		$nbPipes = substr_count($lines, '|');
		$nbSemiColons = substr_count($lines, ';');

		if (
			$nbPipes > 0
			|| $nbSemiColons > 0
		) {
			return false;
		}

		return true;
	}
}
