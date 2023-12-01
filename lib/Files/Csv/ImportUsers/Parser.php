<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\Csv\CsvParserInterface;
use OCA\Workspace\Files\Csv\ImportUsers\Header;
use OCA\Workspace\Files\ManagerConnectionFileInterface;

class Parser implements CsvParserInterface
{

    public function parser(ManagerConnectionFileInterface $file): array {
		$handle = $file->open();
		
		if ($handle === false) {
			throw new \Exception("Imposible to open the $file->getPath() file.");
		}

		$users = [];
		$tableHeader = fgetcsv($handle, 1000, ","); // ignore the first line
		$tableHeader = array_map('strtolower', $tableHeader);

		$nameIndex = HeaderExtractor::getIndex(Header::DISPLAY_NAME, $tableHeader);
		$roleIndex = HeaderExtractor::getIndex(Header::ROLE, $tableHeader);

		while (($data = fgetcsv($handle, 1000, ",")) !== false) {
			$users[] = ['name' => $data[$nameIndex], 'role' => $data[$roleIndex]];
		}
		$file->close();

		return $users;
	}
}
