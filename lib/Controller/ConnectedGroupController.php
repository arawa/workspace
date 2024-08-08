<?php

namespace OCA\Workspace\Controller;

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Group\User\UserGroup;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCP\AppFramework\Controller;
use OCP\IGroupManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use Psr\Log\LoggerInterface;

class ConnectedGroupController extends Controller {
    public function __construct(
        private GroupfolderHelper $folderHelper,
		private LoggerInterface $logger,
        private IGroupManager $groupManager,
        private SpaceMapper $spaceMapper,
        private UserGroup $userGroup
    )
    {
    }

    /**
	 * 
	 * Add a group connected to a workspace/groupfolder.
	 * 
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 * 
	 * @param int $spaceId
	 * @param string $gid
	 */
	public function addGroup(int $spaceId, string $gid): JSONResponse {

		if(!$this->groupManager->groupExists($gid)) {
			$message = sprintf("The group %s does not exist", $gid);

			$this->logger->error($message);

			return new JSONResponse(
				[
					'message' => $message,
					'success' => false
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$group = $this->groupManager->get($gid);

		if (str_starts_with($group->getGID(), 'SPACE-')) {
			$message = sprintf("The group %s cannot be added, as it is already a workspace group", $gid);

			$this->logger->error($message);

			return new JSONResponse(
				[
					'message' => $message,
					'success' => false
				],
				Http::STATUS_NOT_FOUND
			);		
		}
		
		$space = $this->spaceMapper->find($spaceId);		
		
		$this->folderHelper->addApplicableGroup(
			$space->getGroupfolderId(),
			$group->getGid(),
		);

		$message = sprintf("The %s group is added to the %s workspace.", $group->getGID(), $space->getSpaceName());

		$this->logger->info($message);

		return new JSONResponse([
			'message' => sprintf("The %s group is added to the %s workspace.", $group->getGID(), $space->getSpaceName()),
			'success' => true
		]);
	}

	/**
	 * Remove a group connected to a workspace/groupfolder.
	 * 
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 */
	public function removeGroup(int $spaceId, string $gid) {
		if(!$this->groupManager->groupExists($gid)) {
			$message = sprintf("The group %s does not exist", $gid);

			$this->logger->error($message);
			
			return new JSONResponse(
				[
					'message' => $message,
					'success' => false
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$group = $this->groupManager->get($gid);

		if (str_starts_with($group->getGID(), 'SPACE-')) {
			
			$message = sprintf("The %s group is not authorized to remove.", $gid);

			$this->logger->error($message);
			
			return new JSONResponse(
				[
					'message' => $message,
					'success' => false
				],
				Http::STATUS_NOT_FOUND
			);		
		}
		
		$space = $this->spaceMapper->find($spaceId);		

		$this->folderHelper->removeApplicableGroup(
			$space->getGroupfolderId(),
			$group->getGID()
		);

		$message = sprintf("The group %s is removed from the workspace %s", $group->getGID(), $space->getSpaceName());
	
		$this->logger->info($message);
		
		return new JSONResponse([
			'message' => sprintf("The group %s is removed from the workspace %s", $group->getGID(), $space->getSpaceName()),
			'success' => true
		]);
	}
}
