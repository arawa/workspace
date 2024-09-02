<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
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

use OCP\Util;
use OCP\IGroupManager;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\Controller;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\WorkspaceService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\ConnectedGroupsService;

class PageController extends Controller {
	public function __construct(
        private GroupfolderHelper $folderHelper,
        private RootFolder $rootFolder,
        private IGroupManager $groupManager,
        private IInitialState $initialState,
		private UserService $userService,
        private LoggerInterface $logger,
        private ConnectedGroupsService $connectedGroups,
        private WorkspaceService $workspaceService
	) {
	}

	/**
	 * Application's main page
	 *
	 * @NoAdminRequired
	 * @NOCSRFRequired
	 */
	public function index(): TemplateResponse {
		Util::addScript(Application::APP_ID, 'workspace-main');		// js/workspace-main.js
		Util::addStyle(Application::APP_ID, 'workspace-style');		// css/workspace-style.css
	
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
			): $workspace;

			$gids = array_keys($space['groups'] ?? []);
			$groups = [];

			$gids = array_filter($gids, fn($gid) => str_starts_with($gid, 'SPACE-'));

			foreach ($gids as $gid) {
				$group = $this->groupManager->get($gid);
				if (is_null($group)) {
					$this->logger->warning(
						"Be careful, the $gid group is not exist in the oc_groups table."
						. " But, it's present in the oc_group_folders_groups table."
						.  "It necessary to recreate it with the occ command."
					);
					continue;
				}
				$groups[] = $group;
			}

			$addedGroups = [];
			foreach($gids as $gid) {
				$addedToGroup = $this->connectedGroups->getConnectedGroupsToSpaceGroup($gid);
				if ($addedToGroup !== null) {
					$addedGroups = array_merge($addedGroups, $addedToGroup);
				}
			}

			$space['groups'] = GroupFormatter::formatGroups($groups);
			$space['added_groups'] = GroupFormatter::formatGroups($addedGroups);
			$space['users'] = $this->workspaceService->addUsersInfo($space);
	
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

        $this->initialState->provideInitialState('init-workspaces', $spaces);

		return new TemplateResponse('workspace', 'index', ['isUserGeneralAdmin' => $this->userService->isUserGeneralAdmin(), 'canAccessApp' => $this->userService->canAccessApp() ]); 	// templates/index.php
	}
}
