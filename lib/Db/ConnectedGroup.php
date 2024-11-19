<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2024 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

class ConnectedGroup extends Entity implements JsonSerializable {

	/**
	 * @var integer
	 */
	protected $spaceId;

	/**
	 * @var string
	 */
	protected $gid;

	public function __construct() {
		$this->addType('space_id', 'integer');
		$this->addType('gid', 'string');
	}

	public function jsonSerialize(): array {
		return [
			'space_id' => (int)$this->getSpaceId(),
			'gid' => $this->getGid(),
		];
	}
}
