<?php

namespace OCA\Workspace\Files\Csv\ImportUsers;

use OCA\Workspace\Files\Csv\CsvParserInterface;
use OCA\Workspace\Files\Csv\CsvReader;
use OCA\Workspace\Files\Csv\ImportUsers\Header;
use OCA\Workspace\Files\ManagerConnectionFileInterface;
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
