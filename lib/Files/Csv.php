<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Workspace\Files;

use Exception;

/**
 * @deprecated
 * @uses OCA\Workspace\Files\Csv\ImportUsers\{Header,HeaderExtractor,HeaderValidator,Parser}
 * @uses OCA\Workspace\Files\Csv\{CheckMimeType,SeparatorDetector}
 */
class Csv {

	public const DISPLAY_NAME = ["username", "displayname", "name"];
	public const ROLE = ["role", "status", "userrole"];

	private function getIndex(array $haystack, array $needles): int|bool {
		$index = null;
		foreach($haystack as $key => $value) {
			$index = array_search($value, $needles);
			if ($index !== false) {
				return $index;
			}
		}
		return false;
	}
	
	public function parser(ManagerConnectionFileInterface $file) {
		$handle = $file->open();
		
		if ($handle === false) {
			throw new Exception("Imposible to open the $file->getPath() file.");
		}

		$users = [];
		// rewind($handle);
		$tableHeader = fgetcsv($handle, 1000, ","); // ignore the first line
		$tableHeader = array_map('strtolower', $tableHeader);

		$nameIndex = $this->getIndex($this::DISPLAY_NAME, $tableHeader);
		$roleIndex = $this->getIndex($this::ROLE, $tableHeader);

		while (($data = fgetcsv($handle, 1000, ",")) !== false) {
			$users[] = ['name' => $data[$nameIndex], 'role' => $data[$roleIndex]];
		}
		$file->close();

		return $users;
	}

	public function hasProperHeader(ManagerConnectionFileInterface $file): bool {
		$res = false;
		if (($handle = $file->open()) !== false) {
			$tableHeader = fgetcsv($handle, 1000, ",");
			$tableHeader = array_map('strtolower', $tableHeader);

			$nameIndex = $this->getIndex(self::DISPLAY_NAME, $tableHeader);
			$roleIndex = $this->getIndex(self::ROLE, $tableHeader);

			$res = ($nameIndex !== false) && ($roleIndex !== false);
		}
		
		$file->close();

		return $res;
	}
}
