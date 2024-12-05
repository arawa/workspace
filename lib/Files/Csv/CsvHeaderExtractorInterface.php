<?php

namespace OCA\Workspace\Files\Csv;

interface CsvHeaderExtractorInterface {
	public static function getIndex(array $haystack, array $needles): int|bool;
	public static function getHeaderName(array $needles, array $haystack): string;
}
