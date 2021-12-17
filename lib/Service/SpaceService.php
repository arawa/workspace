<?php

namespace OCA\Workspace\Service;

use OCA\Workspace\DB\Space;
use OCA\Workspace\DB\SpaceMapper;
use OCP\IGroupManager;
use OCA\Workspace\BadRequestException;

class SpaceService {
    /** @var SpaceMapper */
    private $spaceMapper;

    public function __construct(
        SpaceMapper $spaceMapper,
        IGroupManager $groupManager
    ) {
        $this->spaceMapper = $spaceMapper;
        $this->groupManager = $groupManager;
    }

    public function findAll() {
        return $this->spaceMapper->findAll();
    }

    public function find($id) {
        return $this->spaceMapper->find($id);
    }

    /**
     * @deprecated
     * @see WorkspaceController->destroy().
     */
    public function delete(int $id) {
        return $this->spaceMapper->deleteSpace($id);
    }

    /**
     * @param $spaceName
     * @return object
     * @throws BadRequestException
     * @todo to debug this part
     */
    public function create(string $spaceName, int $folderId) {
        $space = new Space();
        $space->setSpaceName($spaceName);
        $space->setGroupfolderId($folderId);
        $space->setColorCode('#' . substr(md5(mt_rand()), 0, 6)); // mt_rand() (MT - Mersenne Twister) is taller efficient than rand() function.
        $this->spaceMapper->insert($space);
        return $space;
    }

    /**
     * 
     */
    public function updateSpaceName(string $newSpaceName, int $spaceId) {
        return $this->spaceMapper->updateSpaceName($newSpaceName, $spaceId);
    }

    public function updateColorCode(string $colorCode, int $spaceId) {
        return $this->spaceMapper->updateColorCode($colorCode, $spaceId);
    }

    public function checkSpaceNameExist(string $spacename) {
        $checkSpacename = $this->spaceMapper->checkSpaceNameExist($spacename);

        if (array_key_exists('space_name', $checkSpacename)) {
            return true;
        }
       
        return false;
    }
}