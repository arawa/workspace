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

namespace OCA\Workspace\Helper;

use OCA\GroupFolders\Folder\FolderManager;
use OCA\Workspace\Exceptions\GroupFolderFunctionException;
use OCP\AutoloadNotAllowedException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class GroupfolderHelper {
	private ?FolderManager $folderManager = null;
	private string $dependencyInjectionError = '';

	public function __construct(ContainerInterface $appContainer) {
		try {
			$this->folderManager = $appContainer->get(FolderManager::class);
		} catch (ContainerExceptionInterface|AutoloadNotAllowedException $e) {
			// Could not instantiate - probably groupfolders is disabled.
			$this->dependencyInjectionError = $e->getMessage();
		}
	}

	/**
	 * Create a groupfolder.
	 *
	 * @param string $mountpoint
	 * @return integer the folder id of the groupfolder created
	 */
	public function createFolder(string $mountpoint): int {
		try {
			return $this->folderManager->createFolder($mountpoint);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the createFolder function from FolderManager.');
		}
	}

	public function getFolder(int $folderId, int $rootStorageId) {
		try {
			return $this->folderManager->getFolder($folderId, $rootStorageId);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the getFolder function from FolderManager.');
		}
	}

	public function setFolderAcl(int $folderId, bool $acl): void {
		try {
			$this->folderManager->setFolderAcl($folderId, $acl);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the setFolderAcl from FolderManager.');
		}
	}

	public function addApplicableGroup(int $id, string $group): void {
		try {
			$this->folderManager->addApplicableGroup($id, $group);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the addApplicableGroup from FolderManager.');
		}
	}

	/**
	 * Remove a group from a groupfolder.
	 *
	 * @param integer $id is the folderId of the groupfolder.
	 * @param string $gid correspond to the gid of group already present in groupfolder.
	 * @return void
	 */
	public function removeApplicableGroup(int $id, string $gid): void {
		try {
			$this->folderManager->removeApplicableGroup($id, $gid);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the removeApplicableGroup from FolderManager.');
		}
	}

	public function setManageACL(int $folderId, string $type, string $id, bool $manageAcl): void {
		try {
			$this->folderManager->setManageACL($folderId, $type, $id, $manageAcl);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the setManageACL from FolderManager.');
		}
	}

	public function setFolderQuota(int $folderId, int $quota): void {
		try {
			$this->folderManager->setFolderQuota($folderId, $quota);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the setFolderQuota from FolderManager.');
		}
	}

	public function removeFolder(int $folderId): void {
		try {
			$this->folderManager->removeFolder($folderId);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the removeFolder from FolderManager.');
		}
	}

	public function renameFolder(int $folderId, string $newMountPoint):void {
		try {
			$this->folderManager->renameFolder($folderId, $newMountPoint);
		} catch (\Exception $e) {
			throw new GroupFolderFunctionException($e->getMessage() . 'Impossible to use the renameFolder from FolderManager.');
		}
	}
}
