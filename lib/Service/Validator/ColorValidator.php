<?php

namespace OCA\Workspace\Service\Validator;

class ColorValidator {
	public function __construct() {
	}

	public function isHexadecimal(string $hexa): bool {
		$hexa = str_replace('#', '', $hexa);
		return ctype_xdigit($hexa);
	}

	public function validate(string $color): void {
		if (!$this->isHexadecimal($color)) {
			throw new \Exception('Color code must be hexadecimal.');
		}
	}
}
