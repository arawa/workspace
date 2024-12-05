<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\BasicStreamInterface;
use OCA\Workspace\Files\Csv\CsvValidatorInterface;

class HeaderValidator implements CsvValidatorInterface {
	public function validate(BasicStreamInterface $file): bool {
		$res = false;
		if (($handle = $file->open()) !== false) {
			$tableHeader = fgetcsv($handle, 1000, ",");
			$tableHeader = array_map('strtolower', $tableHeader);

			$nameIndex = HeaderExtractor::getIndex(Header::DISPLAY_NAME, $tableHeader);
			$roleIndex = HeaderExtractor::getIndex(Header::ROLE, $tableHeader);

			$res = ($nameIndex !== false) && ($roleIndex !== false);
		}
		
		$file->close();

		return $res;
	}

}
