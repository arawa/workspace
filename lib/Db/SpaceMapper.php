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

    /**
     * Work
     */
    public function find($id) {
        $qb = $this->db->getQueryBuilder();
        $query = $qb->select('*')
           ->from($this->getTableName())
           ->where(
               $qb->expr()->eq('space_id', $qb->createNamedParameter($id, $qb::PARAM_INT))
           );
        return $this->findEntity($query);
    }

    /**
     * work
     */
    public function findAll($limit=null, $offset=null) {
        $qb = $this->db->getQueryBuilder();

        $qb->select('*')
           ->from($this->getTableName())
           ->setMaxResults($limit)
           ->setFirstResult($offset);

        return $this->findEntities($qb);
    }

    /**
     * @deprecated
     * @see WorkspaceController->destroy().
     */
    public function deleteSpace(int $id) {
        $qb = $this->db->getQueryBuilder();

        $qb->delete('work_spaces')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id))
            )
            ->execute();
    }

    public function updateSpaceName(string $newSpaceName, int $spaceId) {
        $qb = $this->db->getQueryBuilder();

        $qb
            ->update('work_spaces')
            ->set('space_name', $qb->createNamedParameter($newSpaceName))
            ->where($qb->expr()->eq('space_id', $qb->createNamedParameter($spaceId)));

        $qb->execute();

        return $this->find($spaceId);
    }

}