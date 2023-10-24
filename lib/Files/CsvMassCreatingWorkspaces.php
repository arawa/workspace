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

class CsvMassCreatingWorkspaces extends CsvAbstract implements CsvInterface {

	public const WORKSPACE_FIELD = ["workspace-name", "spacename"];
	public const USER_FIELD = ["user", "uid", "WorkspaceManager", "workspace-manager"];

	public function __construct() {
		parent::__construct();
	}

	public function parser(string $path): array {
		$stream = fopen($path, 'r');

		// ignore the header
		$tableHeader = fgetcsv($stream, 1000, ',');

		$tableHeader = array_map('strtolower', $tableHeader);

		$workspaceIndex = $this->getIndex($this::WORKSPACE_FIELD, $tableHeader);
		$userIndex = $this->getIndex($this::USER_FIELD, $tableHeader);

		$data = [];

		while (($dataCsv = fgetcsv($stream, 1000, ',')) !== false) {
			$userUid = $dataCsv[$userIndex];

			if (empty($dataCsv[$userIndex])) {
				$userUid = null;
			}

			$data[] = [
				'workspace_name' => $dataCsv[$workspaceIndex],
				'user_uid' => $userUid,
			];
		}

		fclose($stream);

		return $data;
	}

	public function hasProperHeader(string $path): bool {
		
		$res = true;

		$stream = fopen($path, 'r');

		// ignore the header
		$tableHeader = fgetcsv($stream, 1000, ',');

		$tableHeader = array_map('strtolower', $tableHeader);

		$workspaceField = $this->getIndex(self::WORKSPACE_FIELD, $tableHeader);
		$uidField = $this->getIndex(self::USER_FIELD, $tableHeader);

		$res = ($workspaceField !== false) && ($uidField !== false);
		
		fclose($stream);

		return $res;
	}
}
