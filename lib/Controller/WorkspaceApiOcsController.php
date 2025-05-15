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
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'GET',
		url: '/api/v1/spaces/{id}/groups',
		requirements: ['id' => '\d+']
	)]
	public function findGroupsFromWorkspace(int $id): Response {
		
		$space = $this->spaceMapper->find($id);

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		if ($groupfolder === false) {
			$this->logger->error('Failed loading groupfolder ' . $space->getGroupfolderId());
			return new DataResponse(
				[
					'message' => 'Failed loading groupfolder ' . $space->getGroupfolderId(),
					'success' => false
				],
				Http::STATUS_BAD_REQUEST
			);
		}

		$gids = array_keys($groupfolder['groups']);

		$groups = array_map(fn ($gid) => $this->groupManager->get($gid), $gids);

		$groupsFormatted = GroupFormatter::formatGroups($groups);

		$this->logger->info("Successfully find groups from the {$id} workspace.");

		return new DataResponse($groupsFormatted, Http::STATUS_OK);
	}
}
