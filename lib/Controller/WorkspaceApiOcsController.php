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

use OCA\Workspace\Attribute\WorkspaceManagerRequired;
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IGroupManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

/**
 * @psalm-import-type WorkspaceSpace from ResponseDefinitions
 */
class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private GroupfolderHelper $folderHelper,
		private IGroupManager $groupManager,
		private LoggerInterface $logger,
		private RootFolder $rootFolder,
		private SpaceManager $spaceManager,
		private UserService $userService,
		private WorkspaceService $workspaceService,
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Returns a workspace by its ID
	 *
	 * @param int $id Represents the ID of a workspace
	 * @return DataResponse<Http::STATUS_OK, WorkspaceSpace, array{}>
	 * @throws OCSNotFoundException when no groupfolder is associated with the given space ID
	 * @throws OCSException for all unknown errors
	 *
	 * 200: Workspace returned
	 *
	 */
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'GET',
		url: '/api/v1/space/{id}',
		requirements: ['id' => '\d+']
	)]
	public function find(int $id): DataResponse {
		try {
			$space = $this->spaceManager->get($id);
		} catch (\Exception $e) {
			if ($e instanceof NotFoundException) {
				throw new OCSNotFoundException($e->getMessage());
			}

			throw new OCSException($e->getMessage());
		}

		return new DataResponse($space, Http::STATUS_OK);
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
	 * 200: Workspaces returned
	 */
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/api/v1/spaces')]
	public function findAll(): Response {
		$workspaces = $this->workspaceService->getAll();
		$spaces = [];
		foreach ($workspaces as $workspace) {
			$folderInfo = $this->folderHelper->getFolder(
				$workspace['groupfolder_id'],
				$this->rootFolder->getRootFolderStorageId()
			);
			$space = ($folderInfo !== false) ? array_merge(
				$folderInfo,
				$workspace
			) : $workspace;

			$gids = array_keys($space['groups'] ?? []);
			$wsGroups = [];
			$space['users'] = (object)[];
			$addedGroups = [];

			foreach ($gids as $gid) {
				$group = $this->groupManager->get($gid);
				if (is_null($group)) {
					$this->logger->warning(
						"Be careful, the $gid group does not exist in the oc_groups table."
						. ' The group is still present in the oc_group_folders_groups table.'
						. ' To fix this inconsistency, recreate the group using occ commands.'
					);
					continue;
				}
				if (UserGroup::isWorkspaceGroup($group)) {
					$wsGroups[] = $group;
				} else {
					$addedGroups[] = $group;
				}

				if (UserGroup::isWorkspaceUserGroupId($gid)) {
					$space['userCount'] = $group->count();
				}
			}

			$space['groups'] = GroupFormatter::formatGroups($wsGroups);
			$space['added_groups'] = (object)GroupFormatter::formatGroups($addedGroups);

			$users = $this->workspaceService->addUsersInfo($space);
			$space['users'] = $users;

			$spaces[] = $space;
		}
		// We only want to return those workspaces for which the connected user is a manager
		if (!$this->userService->isUserGeneralAdmin()) {
			$this->logger->debug('Filtering workspaces');
			$filteredWorkspaces = array_values(array_filter($spaces, function ($space) {
				return $this->userService->isSpaceManagerOfSpace($space);
			}));
			$spaces = $filteredWorkspaces;
		}

		return new DataResponse($spaces, Http::STATUS_OK);
	}
}
