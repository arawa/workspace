<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\Csv\CsvReader;
use OCA\Workspace\Files\ManagerConnectionFileInterface;

class ValuesValidator
{
    public function validateRoles(ManagerConnectionFileInterface $file): bool {
        $res = true;
		
        $csvReader = new CsvReader($file);
		$index = HeaderExtractor::getHeaderName($csvReader->headers, Header::ROLE);
        foreach ($csvReader->read() as $data) {
			$role = strtolower($data[$index]);

            if (!in_array($role, Values::ROLES)) return false;
		}

		return $res;
    }
}
