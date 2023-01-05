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

use OCP\AppFramework\Http;
use OCA\Workspace\BadRequestException;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\WorkspaceNameExistException;

class WorkspaceCheckService {
	private SpaceService $spaceService;

	public function __construct(SpaceService $spaceService) {
		$this->spaceService = $spaceService;
	}


	/**
	 * Check if the space name contains specials characters or a blank into the end its name.
	 * @param string $spacename
	 * @return
	 * @throws BadRequestException
	 */
	public function containSpecialChar(string $spacename) {
		if (preg_match('/[~<>{}|;.:,!?\'@#$+()%\\\^=\/&*\[\]]/', $spacename)) {
			throw new BadRequestException('Your Workspace name must not contain the following characters: [ ~ < > { } | ; . : , ! ? \' @ # $ + ( ) - % \ ^ = / & * ]');
		}

		return;
	}


	/**
	 * Check if the space name exist in groupfolders or workspace
	 * @return
	 * @throws WorkspaceNameExistException
	 */
	public function isExist(string $spacename) {
		if ($this->spaceService->checkSpaceNameExist($spacename)) {
			throw new WorkspaceNameExistException('The ' . $spacename . ' space name already exist', Http::STATUS_CONFLICT);
		}

		return;
	}
}
