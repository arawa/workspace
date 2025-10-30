<?php

/**
 * @copyright Copyright (c) 2022 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Service\Workspace;

use OCA\Workspace\Exceptions\BadRequestException;
use OCA\Workspace\Service\SpaceService;

class WorkspaceCheckService {

	public const CHARACTERS_SPECIAL = "[~<>{}|;.:,!?\'@#$+()%\\\^=\/&*\[\]]";

	public function __construct(
		private SpaceService $spaceService,
	) {
	}


	/**
	 * Check if the space name contains specials characters or a blank into the end its name.
	 * @param string $spacename
	 * @throws BadRequestException
	 */
	public function containSpecialChar(string $spacename): bool {
		if (preg_match(sprintf('/%s/', self::CHARACTERS_SPECIAL), $spacename)) {
			return true;
		}

		return false;
	}


	/**
	 * Check if the space name exist in groupfolders or workspace
	 */
	public function isExist(string $spacename): bool {
		if ($this->spaceService->checkSpaceNameExist($spacename)) {
			return true;
		}

		return false;
	}

	public function spacenamesIsDuplicated(array $spaces): bool {
		$workspaceNames = [];

		foreach ($spaces as $space) {
			$workspaceNames[] = $space['workspace_name'];
		}

		$workspaceNamesDiff = array_values(
			array_diff_assoc($workspaceNames, array_unique($workspaceNames))
		);

		return !empty($workspaceNamesDiff);
	}
}
