<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

abstract class CsvAbstract {
	
	private const MIME_CSV_TYPES = [ 'text/csv', 'application/csv' ];
	
	public function __construct() {
	}

	public function isCsvFile(string $path): bool {
		$mimetype = $this->getMimeType($path);

		if (in_array($mimetype, self::MIME_CSV_TYPES)) {
			return true;
		}

		return false;
	}

	public function getMimeType(string $path): string|false {
		return mime_content_type($path);
	}

	public function getIndex(array $haystack, array $needles): int|bool {
		$index = null;
		foreach($haystack as $key => $value) {
			$index = array_search($value, $needles);
			if ($index !== false) {
				return $index;
			}
		}
		return false;
	}

	/**
	 * @param resource $stream
	 */
	protected function next($stream): array|false {
		return fgetcsv($stream, 1000, ',');
	}
}
