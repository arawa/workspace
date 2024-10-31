<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Space;

use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Exceptions\BadRequestException;
use OCA\Workspace\Exceptions\CreateWorkspaceException;
use OCA\Workspace\Exceptions\WorkspaceNameExistException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCP\AppFramework\Http;

class SpaceManager {
	public function __construct(
		private GroupfolderHelper $folderHelper,
		private RootFolder $rootFolder,
		private WorkspaceCheckService $workspaceCheck,
		private UserGroup $userGroup,
		private SpaceMapper $spaceMapper,
		private WorkspaceManagerGroup $workspaceManagerGroup,
	) {
	}
	
	public function create(string $spacename): array {
		if ($spacename === false ||
			$spacename === null ||
			$spacename === ''
		) {
			throw new BadRequestException('spaceName must be provided');
		}

		if ($this->workspaceCheck->containSpecialChar($spacename)) {
			throw new BadRequestException('Your Workspace name must not contain the following characters: ' . implode(' ', str_split(WorkspaceCheckService::CHARACTERS_SPECIAL)));
		}
		
		if ($this->workspaceCheck->isExist($spacename)) {
			throw new WorkspaceNameExistException(
                title: 'Error - Duplicate space name',
                message: "This space or groupfolder already exist. Please, input another space.\nIf \"toto\" space exist, you cannot create the \"tOTo\" space.\nMake sure you the groupfolder doesn't exist."
            );
		}

		$spacename = $this->deleteBlankSpaceName($spacename);

		$folderId = $this->folderHelper->createFolder($spacename);

		$space = new Space();
		$space->setSpaceName($spacename);
		$space->setGroupfolderId($folderId);
		$space->setColorCode('#' . substr(md5(mt_rand()), 0, 6));  // mt_rand() (MT - Mersenne Twister) is taller efficient than rand() function.
		$this->spaceMapper->insert($space);


		if (is_null($space)) {
			throw new CreateWorkspaceException('Error to create a space.', Http::STATUS_CONFLICT);
		}

		$newSpaceManagerGroup = $this->workspaceManagerGroup->create($space);
		$newSpaceUsersGroup = $this->userGroup->create($space);

		$this->folderHelper->setFolderAcl($folderId, true);

		$this->folderHelper->addApplicableGroup(
			$folderId,
			$newSpaceManagerGroup->getGID(),
		);

		$this->folderHelper->addApplicableGroup(
			$folderId,
			$newSpaceUsersGroup->getGID(),
		);

		$this->folderHelper->setManageACL(
			$folderId,
			'group',
			$newSpaceManagerGroup->getGID(),
			true
		);

		$groupfolder = $this->folderHelper->getFolder(
			$folderId,
			$this->rootFolder->getRootFolderStorageId()
		);

		return [
			'name' => $space->getSpaceName(),
			'id_space' => $space->getId(),
			'folder_id' => $space->getGroupfolderId(),
			'color' => $space->getColorCode(),
			'groups' => GroupFormatter::formatGroups([
				$newSpaceManagerGroup,
				$newSpaceUsersGroup
			]),
			'quota' => $groupfolder['quota'],
			'size' => $groupfolder['size'],
			'acl' => $groupfolder['acl'],
			'manage' => $groupfolder['manage']
		];
	}

	public function get(int $spaceId): array {

		$space = $this->spaceMapper->find($spaceId)->jsonSerialize();
		$workspace = array_merge(
			$this->folderHelper->getFolder($space['groupfolder_id'], $this->rootFolder->getRootFolderStorageId()),
			$space
		);

		return $workspace;
	}

	public function attachGroup(int $folderId, string $gid): void {
		$this->folderHelper->addApplicableGroup($folderId, $gid);
	}

	/**
	 * @param string $spaceName it's the space name
	 * @return string whithout the blank to start and end of the space name
	 * @todo move this method
	 */
	private function deleteBlankSpaceName(string $spaceName): string {
		return trim($spaceName);
	}
}
