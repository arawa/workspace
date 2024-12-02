<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\BasicStreamInterface;
use OCA\Workspace\Files\Csv\CsvParserInterface;
use OCA\Workspace\Files\Csv\CsvReader;
use OCA\Workspace\Users\Formatter\UserImportedFormatter;

class Parser implements CsvParserInterface {

	/**
	 * @return UserImportedFormatter[]
	 */
	public function parser(BasicStreamInterface $file): array {
		$users = [];

		$csvReader = new CsvReader($file);

		$uid = HeaderExtractor::getHeaderName($csvReader->headers, Header::DISPLAY_NAME);
		$role = HeaderExtractor::getHeaderName($csvReader->headers, Header::ROLE);

		foreach ($csvReader->read() as $data) {
			$users[] = new UserImportedFormatter($data[$uid], $data[$role]);
		}

		return $users;
	}
}
