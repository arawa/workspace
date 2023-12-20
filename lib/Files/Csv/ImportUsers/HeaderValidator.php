<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\BasicStreamInterface;
use OCA\Workspace\Files\Csv\CsvValidatorInterface;
<<<<<<< HEAD
=======
use OCA\Workspace\Files\BasicStreamInterface;
>>>>>>> f819151 (refactor(Files): Rename an interface and create a new)

class HeaderValidator implements CsvValidatorInterface {
<<<<<<< HEAD
	public function validate(BasicStreamInterface $file): bool {
=======
	public function validate(ManagerConnectionFileInterface $file): bool {
>>>>>>> 5d45ff9 (style(php): run composer cs:fix)
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
