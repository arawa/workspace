<?php

namespace OCA\Workspace\Service;

use OCA\Workspace\DB\Space;
use OCA\Workspace\DB\SpaceMapper;

class SpaceService {
    /** @var SpaceMapper */
    private $spaceMapper;

    public function __construct(SpaceMapper $spaceMapper) {
        $this->spaceMapper = $spaceMapper;
    }

    public function findAll() {
        return $this->spaceMapper->findAll();
    }

    public function find($id) {
        return $this->spaceMapper->find($id);
    }

    public function delete(int $id) {
        return $this->spaceMapper->deleteSpace($id);
    }
}