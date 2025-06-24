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

namespace OCA\Workspace\Files\MassiveWorkspaceCreation;

use OCA\Workspace\Files\Connection\Terminal;
use OCA\Workspace\Files\CsvAbstract;
use OCA\Workspace\Files\CsvInterface;

/**
 * Allow to parse a csv file to create massive workspaces.
 */
class Csv extends CsvAbstract implements CsvInterface {

	public const WORKSPACE_FIELD = ['workspace-name', 'spacename'];
	public const USER_FIELD = ['user', 'uid', 'WorkspaceManager', 'workspace-manager'];
	public const QUOTA_FIELD = [ 'quota' ];

	public function __construct(
		private Terminal $managerConnectionFile,
	) {
		parent::__construct();
	}

	public function parser(string $path): array {
		$stream = $this->managerConnectionFile->open($path);

		// ignore the header
		$tableHeader = $this->next($stream);

		$tableHeader = array_map('strtolower', $tableHeader);

		$workspaceIndex = $this->getIndex($this::WORKSPACE_FIELD, $tableHeader);
		$userIndex = $this->getIndex($this::USER_FIELD, $tableHeader);
		$quotaIndex = $this->getIndex($this::QUOTA_FIELD, $tableHeader);

		$data = [];

		while (($dataCsv = $this->next($stream)) !== false) {
			$userUid = $dataCsv[$userIndex];

			if (empty($dataCsv[$userIndex])) {
				$userUid = null;
			}

			$quota = $dataCsv[$quotaIndex];
			if (empty($dataCsv[$quotaIndex])) {
				$quota = '-3';
			}

			$data[] = [
				'workspace_name' => $dataCsv[$workspaceIndex],
				'user_uid' => $userUid,
				'quota' => $quota
			];
		}

		$this->managerConnectionFile->close();

		return $data;
	}

	public function hasProperHeader(string $path): bool {

		$res = true;

		$stream = $this->managerConnectionFile->open($path);

		// ignore the header
		$tableHeader = $this->next($stream);

		$tableHeader = array_map('strtolower', $tableHeader);

		$workspaceField = $this->getIndex(self::WORKSPACE_FIELD, $tableHeader);
		$uidField = $this->getIndex(self::USER_FIELD, $tableHeader);
		$quotaField = $this->getIndex(self::QUOTA_FIELD, $tableHeader);

		$res = ($workspaceField !== false)
			&& ($uidField !== false)
			&& ($quotaField !== false);

		$this->managerConnectionFile->close();

		return $res;
	}
}
