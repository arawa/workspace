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
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCP\IGroupManager;

class SpaceService {
	public function __construct(
		private IGroupManager $groupManager,
		private SpaceMapper $spaceMapper,
	) {
	}

	public function findAll(): array {
		return $this->spaceMapper->findAll();
	}

	public function find($id): ?Space {
		return $this->spaceMapper->find($id);
	}

	/**
	 * @deprecated
	 * @see WorkspaceController->destroy().
	 */
	public function delete(int $id): mixed {
		return $this->spaceMapper->deleteSpace($id);
	}

	/**
	 * @param $spaceName
	 * @return Space
	 * @throws BadRequestException
	 * @todo to debug this part
	 */
	public function create(string $spaceName, int $folderId): Space {
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

	public function updateColorCode(string $colorCode, int $spaceId): Space {
		return $this->spaceMapper->updateColorCode($colorCode, $spaceId);
	}

	public function checkSpaceNameExist(string $spacename): bool {
		$checkSpacename = $this->spaceMapper->checkSpaceNameExist($spacename);

		if (!is_bool($checkSpacename)) {
			if (array_key_exists('space_name', $checkSpacename)) {
				return true;
			}
		}

		return false;
	}
}
