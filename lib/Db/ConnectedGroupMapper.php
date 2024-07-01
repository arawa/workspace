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

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class ConnectedGroupMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'work_spaces_add_groups', ConnectedGroup::class);
	}

	/**
	 * @param string $gid
	 * @return ConnectedGroup[]
	 */
	public function findByGid(string $gid): array {
		$qb = $this->db->getQueryBuilder();

		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where('gid = :gid')
			->setParameter('gid', $gid)
		;

		return $this->findEntities($query);
	}

	public function find(int $id): ConnectedGroup {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id, $qb::PARAM_INT))
			)
		;

		return $this->findEntity($query);
	}

	/**
	 * @return ConnectedGroup[]
	 */
	public function findAll(?int $spaceId = null, $limit = null, $offset = null): array {
		$qb = $this->db->getQueryBuilder();

		$qb
			->select('*')
			->from($this->getTableName())
		;

		if (!is_null($spaceId)) {
			$qb
				->where('space_id = :spaceId')
				->setParameter('spaceId', $spaceId)
			;
		}

		$qb
			->setMaxResults($limit)
			->setFirstResult($offset)
		;

		return $this->findEntities($qb);
	}
}
