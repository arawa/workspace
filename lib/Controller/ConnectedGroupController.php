<?php

namespace OCA\Workspace\Controller;

use OCA\Workspace\Db\ConnectedGroupMapper;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IGroupManager;
use OCP\IRequest;

class ConnectedGroupController extends Controller {
    public function __construct(
        private IGroupManager $manager,
        private ConnectedGroupMapper $mapper,
        private SpaceMapper $spaceMapper,
        private ConnectedGroupsService $connectedGroupsService
    )
    {
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return Response
     */
    public function getConnectedGroups(IRequest $request): Response {
        $gidParam = $request->getParam('gid', null);

        if (!is_null($gidParam)) {
            $connectedGroups = $this->mapper->findByGid($gidParam);
            return new JSONResponse($connectedGroups);    
        }

        $connectedGroups = $this->mapper->findAll();
        return new JSONResponse($connectedGroups);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return Response
     */
    public function getConnectedGroupsFromSpaceId(int $spaceId): Response {
		$space = $this->spaceMapper->find($spaceId);

		if (is_null($space))
			throw new \Exception("The space with the $spaceId id is not exist.");

        
        $connectedGroups = $this->mapper->findAll(spaceId: $space->getSpaceId());

        return new JSONResponse($connectedGroups);
    } 

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     *
     * @return Response
     */
    public function addConnectedGroup(?int $spaceId, ?string $groupname): Response
    {
        if (is_null($spaceId) || is_null($groupname))
            return new JSONResponse([ 'message' => 'You must define a value for the spaceId and groupname parameters.']);

        $group = $this->manager->get($groupname);
		$space = $this->spaceMapper->find($spaceId);

		if (is_null($group))
			throw new \Exception("The $groupname is not exist.");

		if (is_null($space))
			throw new \Exception("The space with the $spaceId id is not exist.");

        $userGroup = $this->manager->get('SPACE-U-' . $space->getSpaceId());

        if ($this->connectedGroupsService->hasConnectedgroups($group->getGID(), $userGroup->getGID())) {
            return new JSONResponse([
                'message' => 'Alreaydy exist',
                'data' => null
            ]);
        }

        $added = $this->connectedGroupsService->add($group, $space);

        $spacename = $space->getSpaceName();
        if (!$added) {
            throw new \Exception(
                sprintf("The %s group didn't add in the %s workspace", [
                    $group->getGid(),
                    $spacename
                ])
            );
        }

        return new JSONResponse([
            'message' =>
                vsprintf(
                    'The %s is added in the %s workspace',
                    [
                        $group->getGid(),
                        $spacename,
                    ]
                ),
            'data' => []
        ]);
    }
}
