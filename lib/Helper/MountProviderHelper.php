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

use OCA\GroupFolders\Mount\MountProvider;
use OCA\Workspace\Exceptions\MountProviderFunctionException;
use OCP\AutoloadNotAllowedException;
use OCP\Files\Node;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class MountProviderHelper {
	private ?MountProvider $mountProvider = null;
	private string $dependencyInjectionError = '';

	public function __construct(ContainerInterface $appContainer) {
		try {
			$this->mountProvider = $appContainer->get(MountProvider::class);
		} catch (ContainerExceptionInterface|AutoloadNotAllowedException $e) {
			// Could not instantiate - probably groupfolders is disabled.
			$this->dependencyInjectionError = $e->getMessage();
		}
	}

	public function getFolder(int $id, bool $create = true): ?Node {
		try {
			return $this->mountProvider->getFolder($id, $create);
		} catch (\Exception $e) {
			throw new MountProviderFunctionException($e->getMessage(), $e->getCode());
		}
	}
}
