<?php

namespace OCA\Workspace\Db;

use OCP\AppFramework\Db\QBMapper;
use OCP\IDBConnection;

class GroupFoldersGroupsMapper extends QBMapper {

    protected $db;
    
    public function __construct(IDBConnection $db)
    {
        $this->db = $db;
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
}
