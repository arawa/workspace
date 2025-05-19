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

namespace OCA\Workspace\Db;

use JsonSerializable;

use OCP\AppFramework\Db\Entity;

class Space extends Entity implements JsonSerializable {
	/** @var integer */
	protected $groupfolderId;

	/** @var string */
	protected $spaceName;

	/** @var integer */
	protected $spaceId;

	/** @var string */
	protected $colorCode;

	public function __construct() {
		$this->addType('id', 'integer');
		$this->addType('groupfolder_id', 'integer');
		$this->addType('space_name', 'string');
		$this->addType('color_code', 'string');
	}

	/**
	 * TODO: When it's wrote '$this->getId()', it prints well the
	 * id when it created (POST). But, with GETs method, it has to
	 * write $this->getSpaceId().
	 */
	public function jsonSerialize(): array {
		return [
			'id' => (int)$this->getSpaceId(),
			'groupfolder_id' => (int)$this->groupfolderId,
			'name' => $this->spaceName,
			'color_code' => $this->colorCode,
		];
	}

	public function getSpaceName(): string {
		return $this->spaceName;
	}
}
