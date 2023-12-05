<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

<<<<<<< HEAD
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
=======
use OCA\Workspace\Files\Csv\CsvReader;
use OCA\Workspace\Files\Csv\CsvParserInterface;
use OCA\Workspace\Files\Csv\CsvReader;
use OCA\Workspace\Files\Csv\ImportUsers\Header;
use OCA\Workspace\Files\ManagerConnectionFileInterface;
<<<<<<< HEAD
use OCA\Workspace\Files\Csv\ImportUsers\HeaderExtractor;
>>>>>>> b5211d5 (refactor(php): Improvement in reading speed)
use OCA\Workspace\Users\Formatter\UserImportedFormatter;

class Parser implements CsvParserInterface {

<<<<<<< HEAD
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
=======
    /**
     * @return UserImportedFormatter[]
     */
    public function parser(ManagerConnectionFileInterface $file): array {
        $users = [];

        $csvReader = new CsvReader($file);

        $uid = HeaderExtractor::getHeaderName($csvReader->headers, Header::DISPLAY_NAME);
        $role = HeaderExtractor::getHeaderName($csvReader->headers, Header::ROLE);

        foreach ($csvReader->read() as $data ) {
            $users[] = new UserImportedFormatter($data[$uid], $data[$role]);
        }
>>>>>>> b5211d5 (refactor(php): Improvement in reading speed)
=======
use OCA\Workspace\Users\Formatter\UserImportedFormatter;

class Parser implements CsvParserInterface
{

    /**
     * @return UserImportedFormatter[]
     */
    public function parser(ManagerConnectionFileInterface $file): array {
		$users = [];

        foreach ((new CsvReader)($file) as $data ) {
            $keys = array_keys($data);
            $uid = $this->getKey($keys, Header::DISPLAY_NAME);
            $role = $this->getKey($keys, Header::ROLE);
            $users[] = new UserImportedFormatter($data[$uid], $data[$role]);
        }
>>>>>>> 68bb094 (refactor(): Read a csv file is faster than before)

		return $users;
	}

    private function getKey(array $needle, array $haystack): string {
        return array_values(
            array_filter(
                $needle,
                fn($key) => in_array($key, $haystack)
            )
        )[0];
    }
}
