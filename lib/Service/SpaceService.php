<?php

namespace OCA\Workspace\Service;

use OCA\Workspace\DB\Space;
use OCA\Workspace\DB\SpaceMapper;
use OCP\IGroupManager;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\BadRequestException;
use OCA\Workspace\Controller\Exceptions\CreateGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AssignGroupToGroupFolderException;
use OCA\Workspace\Controller\Exceptions\AclGroupFolderException;
use OCA\Workspace\Controller\Exceptions\ManageAclGroupFolderException;
use OCP\AppFramework\Http;
use OCA\Workspace\Service\GroupfolderService;
use OCP\AppFramework\Http\JSONResponse;


class SpaceService {
    /** @var SpaceMapper */
    private $spaceMapper;

    /** @var IGroupManager */
    private $groupManager;

    /** @var GroupfolderService */
    private $groupfolderService;

    public function __construct(
        SpaceMapper $spaceMapper,
        IGroupManager $groupManager,
        GroupfolderService $groupfolderService
    ) {
        $this->spaceMapper = $spaceMapper;
        $this->groupManager = $groupManager;
        $this->groupfolderService = $groupfolderService;
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
     * @return array
     * @throws BadRequestException
     */
    public function create(string $spaceName) {

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
}