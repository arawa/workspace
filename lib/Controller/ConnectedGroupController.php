<?php

namespace OCA\Workspace\Controller;

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminUserGroup;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Slugger;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class ConnectedGroupController extends Controller {
	public function __construct(
		private GroupfolderHelper $folderHelper,
		private LoggerInterface $logger,
		private RootFolder $rootFolder,
		private IGroupManager $groupManager,
		private AdminGroup $adminGroup,
		private AdminUserGroup $adminUserGroup,
		private SpaceMapper $spaceMapper,
		private SpaceManager $spaceManager,
		private UserGroup $userGroup,
		private UserService $userService,
		private WorkspaceService $workspaceService,
	) {
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

		if (!$this->groupManager->groupExists($gid)) {
			$message = sprintf('The group %s does not exist', $gid);

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

		if (UserGroup::isWorkspaceGroup($group)) {
			$message = sprintf('The group %s cannot be added, as it is already a workspace group', $gid);

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
		$spaceArray = $this->spaceManager->get($spaceId);

		$this->folderHelper->addApplicableGroup(
			$space->getGroupfolderId(),
			$group->getGid(),
		);


		$dataSpace = $this->spaceManager->get($spaceId);
		foreach ($group->getUsers() as $user) {
			if (!$user->isEnabled()) {
				continue;
			}

			$users[$user->getUID()] = $this->userService->formatUser(
				$user,
				$spaceArray,
				'user'
			);
		};

		foreach ($users as &$user) {
			if (array_key_exists('groups', $user) && $user['is_connected'] === true) {
				array_push($user['groups'], UserGroup::get($space->getSpaceId()));
			}
		}

		$message = sprintf('The %s group is added to the %s workspace.', $group->getGID(), $space->getSpaceName());

		$this->logger->info($message);

		return new JSONResponse([
			'message' => sprintf('The %s group is added to the %s workspace.', $group->getGID(), $space->getSpaceName()),
			'success' => true,
			'users' => $users,
			'slug' => Slugger::slugger($gid),
		]);
	}

	/**
	 * Remove a group connected to a workspace/groupfolder.
	 *
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 */
	public function removeGroup(int $spaceId, string $gid) {
		if (!$this->groupManager->groupExists($gid)) {
			$message = sprintf('The group %s does not exist', $gid);

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

		if (UserGroup::isWorkspaceGroup($group)) {

			$message = sprintf('You %s group is not authorized to be removed.', $gid);

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

		$message = sprintf('The group %s is removed from the workspace %s', $group->getGID(), $space->getSpaceName());

		$this->logger->info($message);

		return new JSONResponse([
			'message' => sprintf('The group %s is removed from the workspace %s', $group->getGID(), $space->getSpaceName()),
			'success' => true
		]);
	}
}
