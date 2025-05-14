<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2025 Baptiste Fotia <baptiste.fotia@arawa.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Workspace\Controller;

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\WorkspaceService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCSController;
use OCP\IGroupManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private GroupfolderHelper $folderHelper,
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
		private RootFolder $rootFolder,
		private SpaceMapper $spaceMapper,
		private WorkspaceService $workspaceService,
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return Response<{
	 * 	id: int,
	 * 	mount_point: string,
	 * 	groups: array,
	 * 	quota: int,
	 * 	size: int,
	 * 	acl: bool,
	 * 	manage: array,
	 * 	groupfolder_id: int,
	 * 	name: string,
	 * 	color_code: string,
	 * 	users: array,
	 * 	userCount: int,
	 * 	added_groups: array
	 * }, Http::STATUS_OK>
	 *
	 * 200: Workspace returned
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'GET',
		url: '/api/v1/spaces/{id}',
		requirements: ['id' => '\d+']
	)]
	public function find(int $id): Response {
		$space = $this->spaceMapper->find($id);

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		if ($groupfolder === false) {
			return new DataResponse(
				[
					'message' => 'Failed loading groupfolder ' . $space->getGroupfolderId(),
					'success' => false
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		$space = array_merge($groupfolder, $space->jsonSerialize());

		$gids = array_keys($space['groups']) ?? [];
		$wsGroups = [];
		$addedGroups = [];
		
		foreach ($gids as $gid) {
			$group = $this->groupManager->get($gid);

			if (UserGroup::isWorkspaceGroup($group)) {
				$wsGroups[] = $group;
			} else {
				$addedGroups[] = $group;
			}
	
			if (UserGroup::isWorkspaceUserGroupId($gid)) {
				$space['userCount'] = $group->count();
			}
		}

		$users = $this->workspaceService->addUsersInfo($space);
		$space['users'] = $users;

		$space['groups'] = GroupFormatter::formatGroups($wsGroups);
		$space['added_groups'] = (object)GroupFormatter::formatGroups($addedGroups);

		$this->logger->info("Find the workspace with {$id} successfully", $space);

		return new DataResponse($space, Http::STATUS_OK);
	}
}
