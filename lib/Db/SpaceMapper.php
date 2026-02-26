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

class SpaceMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'work_spaces', Space::class);
	}

	/**
	 * Work
	 */
	public function find($id): ?Space {
		$qb = $this->db->getQueryBuilder();
		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('space_id', $qb->createNamedParameter($id, $qb::PARAM_INT))
			);

		try {
			return $this->findEntity($query);
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * retrieve a space with its name
	 */
	public function findByName($spacename): Space {
		$qb = $this->db->getQueryBuilder();
		$query = $qb->select('*')
			->from($this->getTableName())
			->where(
				$qb->expr()->eq('space_name', $qb->createNamedParameter($spacename, $qb::PARAM_STR))
			);
		return $this->findEntity($query);
	}

	/**
	 * @param int|null $offset to paginate the workspaces returned
	 * @param int|null $limit to limit the number of workspaces returned
	 * @return Space[]
	 */
	public function findAll(?int $offset = null, ?int $limit = null, ?string $name = null, ?string $uid = null): array {
		$name = $name ? strtolower($name) : null;
		$offset = $offset ? $offset * $limit : $offset;

		$qb = $this->db->getQueryBuilder();

		$qb
			->select(
				'ws.space_id AS space_id',
				'ws.groupfolder_id AS groupfolder_id',
				'ws.color_code AS color_code',
				'ws.space_name AS space_name'
			)
			->from($this->getTableName(), 'ws')
		;

		if ($uid !== null) {
			$qb
				->innerJoin(
					'ws',
					'group_folders_groups',
					'gfg',
					$qb->expr()->eq('ws.groupfolder_id', 'gfg.folder_id'))
				->leftJoin(
					'gfg',
					'group_user',
					'gu',
					$qb->expr()->eq('gfg.group_id', 'gu.gid')
				)
				->andWhere(
					'gu.gid like "SPACE-GE-%"'
				)
				->andWhere('gu.uid = :uid')
				->setParameter('uid', $uid)
			;

		}

		if ($name !== null) {
			$qb
				->andWhere('lower(space_name) like :name')
				->setParameter('name', "%{$name}%")
			;
		}

		if ($limit !== null) {
			$qb->setMaxResults($limit);
		}

		if ($offset !== null) {
			$qb->setFirstResult($offset);
		}

		return $this->findEntities($qb);
	}

	/**
	 * @see WorkspaceController->destroy().
	 */
	public function deleteSpace(int $id): void {
		$qb = $this->db->getQueryBuilder();

		$qb->delete('work_spaces')
			->where($qb->expr()->eq('space_id', $qb->createNamedParameter($id))
			)
			->executeStatement();
	}

	public function updateSpaceName(string $newSpaceName, int $spaceId): Space {
		$qb = $this->db->getQueryBuilder();

		$qb
			->update('work_spaces')
			->set('space_name', $qb->createNamedParameter($newSpaceName))
			->where($qb->expr()->eq('space_id', $qb->createNamedParameter($spaceId)));

		$qb->executeStatement();

		return $this->find($spaceId);
	}

	public function updateColorCode(string $colorCode, int $spaceId): Space {
		$qb = $this->db->getQueryBuilder();

		$qb
			->update('work_spaces')
			->set('color_code', $qb->createNamedParameter($colorCode))
			->where($qb->expr()->eq('space_id', $qb->createNamedParameter($spaceId)));

		$qb->executeStatement();

		return $this->find($spaceId);
	}

	public function checkSpaceNameExist(string $spacename): mixed {
		$qb = $this->db->getQueryBuilder();

		$qb
			->select('space_name')
			->from($this->getTableName())
			->where('upper(space_name) like upper(:spaceName)')
			->setParameter('spaceName', $spacename);

		$cursor = $qb->executeQuery();

		$row = $cursor->fetch();
		$cursor->closeCursor();

		return $row;
	}

	public function countSpaces(?string $name, ?string $uid = null): int {
		$qb = $this->db->getQueryBuilder();
		$name = $name ? strtolower($name) : null;

		$qb
			->select(
				$qb
					->func()
					->count('*', 'count')
			)
			->from(
				$this->getTableName(),
				'ws'
			)
		;

		if ($uid !== null) {
			$qb
				->innerJoin(
					'ws',
					'group_folders_groups',
					'gfg',
					$qb->expr()->eq('ws.groupfolder_id', 'gfg.folder_id'))
				->leftJoin(
					'gfg',
					'group_user',
					'gu',
					$qb->expr()->eq('gfg.group_id', 'gu.gid')
				)
				->andWhere(
					'gu.gid like "SPACE-GE-%"'
				)
				->andWhere('gu.uid = :uid')
				->setParameter('uid', $uid)
			;
		}

		$qb
			->andWhere('lower(space_name) like :name')
			->setParameter('name', "%{$name}%")
		;

		$cursor = $qb->executeQuery();

		$count = (int)$cursor->fetch()['count'];
		$cursor->closeCursor();

		return $count;
	}
}
