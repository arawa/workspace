<?php

namespace OCA\Workspace\Files\Csv;

use OCA\Workspace\Files\FileInterface;

class SeparatorDetector {
	private const SIZE = 1000;
	
	public static function isComma(FileInterface $file): bool {
		$handle = $file->open();
		
		$firstLine = fread($handle, self::SIZE);

		$lines = file($file->getPath(), FILE_SKIP_EMPTY_LINES);
		$totalCount = count($lines);

		$separatorsAsComma = array_filter(
			$lines,
			fn ($line) => str_contains($line, Separator::COMMA)
		);
		
		$commasCount = count($separatorsAsComma);

		$file->close();

		$isComma = 
			(strpos($firstLine, Separator::COMMA) !== false)
			&& ($totalCount === $commasCount)
		;

		return $isComma;
	}
}
