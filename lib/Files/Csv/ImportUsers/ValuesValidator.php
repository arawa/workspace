<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\BasicStreamInterface;
use OCA\Workspace\Files\Csv\CsvReader;
<<<<<<< HEAD
=======
use OCA\Workspace\Files\BasicStreamInterface;
>>>>>>> f819151 (refactor(Files): Rename an interface and create a new)

class ValuesValidator {
<<<<<<< HEAD
	public function validateRoles(BasicStreamInterface $file): bool {
=======
	public function validateRoles(ManagerConnectionFileInterface $file): bool {
>>>>>>> cb320bd (style(php): composer run cs:fix)
		$res = true;
		
		$csvReader = new CsvReader($file);
		$index = HeaderExtractor::getHeaderName($csvReader->headers, Header::ROLE);
		foreach ($csvReader->read() as $data) {
			$role = strtolower($data[$index]);

			if (!in_array($role, Values::ROLES)) {
				return false;
			}
		}

		return $res;
	}
}
