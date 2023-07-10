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

namespace OCA\Workspace\Groups\Workspace;

use OCP\AppFramework\Services\IAppConfig;
use OCA\Workspace\Groups\GroupWorkspaceInterface;

class WorkspaceManagerGroup implements GroupWorkspaceInterface {

	private const GID_SPACE = 'SPACE-';
	private const PREFIX_GID_MANAGERS = self::GID_SPACE . 'GE-';
	private string $DISPLAY_PREFIX_MANAGER_GROUP;

	public function __construct(IAppConfig $appConfig) {
		$this->DISPLAY_PREFIX_MANAGER_GROUP = $appConfig->getAppValue('DISPLAY_PREFIX_MANAGER_GROUP');
	}

    public function get(int $spaceId): string {
        return self::PREFIX_GID_MANAGERS . $spaceId;
    }

    public function getGidPrefix(): string {
        return self::PREFIX_GID_MANAGERS;
    }

    public function getDisplayPrefix(): string
    {
        return $this->DISPLAY_PREFIX_MANAGER_GROUP;
    }
}
