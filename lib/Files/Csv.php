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

class Csv {

    const DISPLAY_NAME = ["username", "displayname", "name"];
    const ROLE = ["role", "status", "userrole"];

	public function parser(array $file): array {
		$users = [];
		if (($handle = fopen($file['tmp_name'], "r")) !== false) {
            $tableHeader = fgetcsv($handle, 1000, ",");
            $tableHeader = array_map('strtolower', $tableHeader);
            $nameIndex = false;
            $roleIndex = false;
            foreach($this::DISPLAY_NAME as $key=>$value) {
                $nameIndex = array_search($value, $tableHeader);
                if ($nameIndex !== false) break;
            }
            foreach($this::ROLE as $key=>$value) {
                $roleIndex = array_search($value, $tableHeader);
                if ($roleIndex !== false) break;
            }
			while (($data = fgetcsv($handle, 1000, ",")) !== false) {
				$users[] = ['name' => $data[$nameIndex], 'role' => $data[$roleIndex]];
			}
			fclose($handle);
		}
		return $users;
	}

    public function hasProperHeader(array $file): bool {
        if (($handle = fopen($file['tmp_name'], "r")) !== false) {
            $tableHeader = fgetcsv($handle, 1000, ",");
            $tableHeader = array_map('strtolower', $tableHeader);
            $nameIndex = false;
            $roleIndex = false;
            foreach($this::DISPLAY_NAME as $key=>$value) {
                $nameIndex = array_search($value, $tableHeader);
                if ($nameIndex !== false) break;
            }
            foreach($this::ROLE as $key=>$value) {
                $roleIndex = array_search($value, $tableHeader);
                if ($roleIndex !== false) break;
            }
            return ($nameIndex !== false) && ($roleIndex !== false);
        }
        return false;
    }
    
}
