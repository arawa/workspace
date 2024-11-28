<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\Csv\CsvHeaderExtractorInterface;

<<<<<<< HEAD
class HeaderExtractor implements CsvHeaderExtractorInterface {
	public static function getIndex(array $haystack, array $needles): int|bool {
=======
class HeaderExtractor implements CsvHeaderExtractorInterface
{
    public static function getIndex(array $haystack, array $needles): int|bool {
>>>>>>> 4e419d2 (refactor(php): Split the import users code)
		$index = null;
		$needles = array_map(function($needle) {
			return preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', '', trim($needle));
		}, $needles);
		foreach($haystack as $key => $value) {
			$index = array_search($value, $needles);
			if ($index !== false) {
				return $index;
			}
		}
		return false;
	}
<<<<<<< HEAD

	/**
	 * @throws \Exception when nothing header is found.
	 */
	public static function getHeaderName(array $needles, array $haystacks): string {
		$found = array_filter(
			$needles,
			fn ($needle) => in_array($needle, $haystacks)
		);

		if (empty($found)) {
			throw new \Exception("The $needles needles is not present in $haystacks haystacks");
		}

		$key = (string)array_values($found)[0];

		return $key;
	}
=======
>>>>>>> 4e419d2 (refactor(php): Split the import users code)
}
