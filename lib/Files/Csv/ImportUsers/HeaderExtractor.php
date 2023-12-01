<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\Csv\CsvHeaderExtractorInterface;

class HeaderExtractor implements CsvHeaderExtractorInterface
{
    public static function getIndex(array $haystack, array $needles): int|bool {
		$index = null;
		foreach($haystack as $key => $value) {
			$index = array_search($value, $needles);
			if ($index !== false) {
				return $index;
			}
		}
		return false;
	}
}
