<?php

namespace OCA\Workspace\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\SpaceService;
use OCP\AppFramework\Http\DataResponse;

class SpaceController extends Controller{

    /** @var SpaceService */
    private $spaceService;

    /** @var SpaceMapper */
    private $spaceMapper;

    public function __construct(
        $AppName,
        IRequest $request,
        SpaceMapper $mapper,
        SpaceService $spaceService
    )
    {
        parent::__construct($AppName, $request);

        $this->spaceMapper = $mapper;

        $this->spaceService = $spaceService;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function find($id){
        return new DataResponse($this->spaceService->find($id));
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function findAll() {
        return new DataResponse($this->spaceService->findAll());
    }

    /**
     * @NoAdminRequired
     * @SpaceAdminRequired
     * @NoCSRFRequired
     */
    public function updateSpaceName($newSpaceName, $spaceId) {
        return new DataResponse($this->spaceService->updateSpaceName($newSpaceName, (int)$spaceId));
    }

    /**
     * @NoAdminRequired
     * @SpaceAdminRequired
     * @NoCSRFRequired
     */
    public function updateColorCode(string $colorCode, int $spaceId) {
        return new DataResponse($this->spaceService->updateColorCode($colorCode, (int)$spaceId));
    }

}
