<?php

namespace OCA\Workspace\Controller;

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Group\User\UserGroup;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCP\AppFramework\Controller;
use OCP\IGroupManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IUser;

class ConnectedGroupController extends Controller {
    public function __construct(
        private GroupfolderHelper $folderHelper,
        private IGroupManager $groupManager,
        private SpaceMapper $spaceMapper,
        private UserGroup $userGroup
    )
    {
    }

	/**
	 * @param IUser[] $users
	 */
	private function getUsers(array $users) {
		foreach ($users as $user) {
			yield $user;
		}
	}

    /**
	 * Undocumented function
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * 
	 * @param int $spaceId
	 * @param string $gid
	 */
	public function addGroup(int $spaceId, string $gid): JSONResponse {

		if(!$this->groupManager->groupExists($gid)) {
			return new JSONResponse(
				[
					sprintf("The %s group is not exist.", $gid),
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$group = $this->groupManager->get($gid);

		if (str_starts_with($group->getGID(), 'SPACE-')) {
			return new JSONResponse(
				[
					sprintf("The %s group is not authorized to add.", $gid),
				],
				Http::STATUS_NOT_FOUND
			);		
		}
		
		$space = $this->spaceMapper->find($spaceId);		

		$workspaceUserGroup = UserGroup::GID_PREFIX . $space->getSpaceId();

		if (!$this->groupManager->groupExists($workspaceUserGroup)) {
			return new JSONResponse(
				[
					sprintf("The %s group is not exist.", $workspaceUserGroup),
				],
				Http::STATUS_NOT_FOUND
			);	
		}
		
		$this->folderHelper->addApplicableGroup(
			$space->getGroupfolderId(),
			$group->getGid(),
		);

		return new JSONResponse([
			'message' => sprintf("The %s group is added to the %s workspace.", $group->getGID(), $space->getSpaceName())
		]);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function removeGroup(int $spaceId, string $gid) {
		if(!$this->groupManager->groupExists($gid)) {
			return new JSONResponse(
				[
					sprintf("The %s group is not exist.", $gid),
				],
				Http::STATUS_NOT_FOUND
			);
		}

		$group = $this->groupManager->get($gid);

		if (str_starts_with($group->getGID(), 'SPACE-')) {
			return new JSONResponse(
				[
					sprintf("The %s group is not authorized to remove.", $gid),
				],
				Http::STATUS_NOT_FOUND
			);		
		}
		
		$space = $this->spaceMapper->find($spaceId);		

		$userGid = UserGroup::GID_PREFIX . $space->getSpaceId();

		if (!$this->groupManager->groupExists($userGid)) {
			return new JSONResponse(
				[
					sprintf("The %s group is not exist.", $userGid),
				],
				Http::STATUS_NOT_FOUND
			);	
		}

		$this->folderHelper->removeApplicableGroup(
			$space->getGroupfolderId(),
			$group->getGID()
		);

		return new JSONResponse([
			'message' => sprintf("The %s group is removed to the %s workspace.", $group->getGID(), $space->getSpaceName())
		]);
	}
}
