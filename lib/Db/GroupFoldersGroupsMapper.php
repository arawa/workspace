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

namespace OCA\Workspace\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class GroupFoldersGroupsMapper extends QBMapper {
	protected $db;
	
	public function __construct(IDBConnection $db) {
		$this->db = $db;
		$this->entityClass = ConnectedGroup::class;
	}
	
	/**
	 * @return array [
	 *      [
	 *          'space_name' => 'Space01',
	 *          'group_id' => 'Mars-9',
	 *      ],
	 *      [
	 *          'space_name' => 'Space02',
	 *          'group_id' => 'Moon-10',
	 *      ],
	 * ]
	 */
	public function getSpacenamesGroupIds() {
		$qb = $this->db->getQueryBuilder();
		$qb
			->select([ 'space_name', 'group_id' ])
			->from('group_folders_groups', 'gf_groups')
			->innerJoin(
				'gf_groups',
				'work_spaces',
				'ws',
				$qb->expr()->eq(
					'ws.groupfolder_id',
					'gf_groups.folder_id'
				)
			)
			->where('group_id not like "SPACE-GE%"')
			->andWhere('group_id not like "SPACE-U%"');

		return $qb->executeQuery()->fetchAll();
	}


	/**
	 * @return array<ConnectedGroup>
	 */
	public function findAllAddedGroups() : array {
		$qb = $this->db->getQueryBuilder();
		$query = $qb
			->select([ 'space_id', 'group_id as gid' ])
			->from('group_folders_groups', 'gf_groups')
			->innerJoin(
				'gf_groups',
				'work_spaces',
				'ws',
				$qb->expr()->eq(
					'ws.groupfolder_id',
					'gf_groups.folder_id'
				)
			)
			->where('group_id not like :wmGroup') // G and GE
			->andWhere('group_id not like :uGroup')
			->setParameter('wmGroup', 'SPACE-G%')
			->setParameter('uGroup', 'SPACE-U%');

		return $this->findEntities($query);
	}

    public function isUserConnectedGroup(string $uid): mixed {
        $qb = $this->db->getQueryBuilder();

        $query = $qb
            ->select('*')
            ->from('group_user')
            ->where('uid = :uid')
            ->setParameter('uid', $uid)
        ;

        $res = $query->executeQuery();

        return $res->fetch();
    }
}
