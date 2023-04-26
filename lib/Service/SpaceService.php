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

namespace OCA\Workspace\Service;

use OCA\Workspace\BadRequestException;
use OCA\Workspace\DB\Space;
use OCA\Workspace\DB\SpaceMapper;
use OCP\IGroupManager;

class SpaceService {
	/** @var SpaceMapper */
	private $spaceMapper;

	public function __construct(
		SpaceMapper $spaceMapper,
		IGroupManager $groupManager
	) {
		$this->spaceMapper = $spaceMapper;
		$this->groupManager = $groupManager;
	}

	public function findAll() {
		return $this->spaceMapper->findAll();
	}

	public function find($id) {
		return $this->spaceMapper->find($id);
	}

	/**
	 * @deprecated
	 * @see WorkspaceController->destroy().
	 */
	public function delete(int $id) {
		return $this->spaceMapper->deleteSpace($id);
	}

	/**
	 * @param $spaceName
	 * @return object
	 * @throws BadRequestException
	 * @todo to debug this part
	 */
	public function create(string $spaceName, int $folderId) {
		$space = new Space();
		$space->setSpaceName($spaceName);
		$space->setGroupfolderId($folderId);
		$space->setColorCode('#' . substr(md5(mt_rand()), 0, 6)); // mt_rand() (MT - Mersenne Twister) is taller efficient than rand() function.
		$this->spaceMapper->insert($space);
		return $space;
	}

	/**
	 *
	 */
	public function updateSpaceName(string $newSpaceName, int $spaceId) {
		return $this->spaceMapper->updateSpaceName($newSpaceName, $spaceId);
	}

	public function updateColorCode(string $colorCode, int $spaceId) {
		return $this->spaceMapper->updateColorCode($colorCode, $spaceId);
	}

	public function checkSpaceNameExist(string $spacename) {
		$checkSpacename = $this->spaceMapper->checkSpaceNameExist($spacename);

		if (!is_bool($checkSpacename)) {
			if (array_key_exists('space_name', $checkSpacename)) {
				return true;
			}
		}
		
		return false;
	}
}
