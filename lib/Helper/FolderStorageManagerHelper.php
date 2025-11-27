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

namespace OCA\Workspace\Helper;

use OCA\GroupFolders\Folder\FolderDefinition;
use OCA\GroupFolders\Mount\FolderStorageManager;
use OCA\Workspace\Exceptions\FolderStorageManagerFunctionException;
use OCP\AutoloadNotAllowedException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class FolderStorageManagerHelper {
	private ?FolderStorageManager $folderStorageManager = null;
	private string $dependencyInjectionError = '';

	public function __construct(ContainerInterface $appContainer) {
		try {
			$this->folderStorageManager = $appContainer->get(FolderStorageManager::class);
		} catch (ContainerExceptionInterface|AutoloadNotAllowedException $e) {
			// Could not instantiate - probably groupfolders is disabled.
			$this->dependencyInjectionError = $e->getMessage();
		}
	}

	public function deleteStoragesForFolder(FolderDefinition $folder): void {
		try {
			$this->folderStorageManager->deleteStoragesForFolder($folder);
		} catch (\Exception $e) {
			throw new FolderStorageManagerFunctionException($e->getMessage(), $e->getCode());
		}
	}
}
