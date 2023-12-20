<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

<<<<<<< HEAD
use OCA\Workspace\Files\BasicStreamInterface;
use OCA\Workspace\Files\Csv\CsvParserInterface;
use OCA\Workspace\Files\Csv\CsvReader;
<<<<<<< HEAD
=======
use OCA\Workspace\Files\BasicStreamInterface;
>>>>>>> f819151 (refactor(Files): Rename an interface and create a new)
=======
use OCA\Workspace\Files\Csv\CsvParserInterface;
use OCA\Workspace\Files\Csv\CsvReader;
use OCA\Workspace\Files\ManagerConnectionFileInterface;
>>>>>>> 5d45ff9 (style(php): run composer cs:fix)
use OCA\Workspace\Users\Formatter\UserImportedFormatter;

class Parser implements CsvParserInterface {

	/**
	 * @return UserImportedFormatter[]
	 */
<<<<<<< HEAD
	public function parser(BasicStreamInterface $file): array {
=======
	public function parser(ManagerConnectionFileInterface $file): array {
>>>>>>> 5d45ff9 (style(php): run composer cs:fix)
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
