<?php

namespace OCA\Workspace\Db;

use OCP\IDBConnection;

class OCFileCacheMapper {

    private IDBConnection $db;
    
    public function __construct(IDBConnection $db)
    {
        $this->db = $db;
    }

    public function getGroupFolderId(string $fileId): mixed {
        
        $query = $this->db->getQueryBuilder();

        $query
            ->select('path')
            ->from('filecache')
            ->where(
                $query
                    ->expr()
                    ->eq(
                        'fileid',
                        $query->createNamedParameter($fileId)
                    )
            );

        $result = $query->executeQuery()->fetch();
        return $result;
    }
}
