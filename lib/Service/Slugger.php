<?php

namespace OCA\Workspace\Service;

class Slugger {
	public static function slugger(string $pattern): string {
		
		$slug = self::encodeURL($pattern);

		$slug = self::ignoreBlank($slug);

		$slug = self::encodeURL($slug);
		
		return $slug;
	}

	private static function encodeURL(string $pattern): string {
		return urlencode($pattern);
	}
	
	private static function ignoreBlank(string $pattern): string {
		return str_replace('+', '%20', $pattern);
	}
}
