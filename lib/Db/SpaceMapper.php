<?php

namespace OCA\Workspace\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\QBMapper;
use OCA\Workspace\AppInfo\Application;
use OCP\AppFramework\Http\JSONResponse;

class SpaceMapper extends QBMapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'work_spaces', Space::class);
    }

    public function find(int $id) {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')
           ->from('work_spaces')
           ->where(
               $qb->expr()->eq('id', $qb->createNamedParameter($id, $qb::PARAM_INT))
            );
        return $this->findEntity($qb);
    }

    public function findAll($limit=null, $offset=null) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    public function deleteSpace(int $id) {
        $qb = $this->db->getQueryBuilder();

        $qb->delete('work_spaces')
            ->where($qb->expr()->eq('space_id', $qb->createNamedParameter($id))
            )
            ->execute();
    }

}