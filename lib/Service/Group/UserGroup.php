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

namespace OCA\Workspace\Service\Group;

use OCA\Workspace\Exceptions\CreateGroupException;
use OCA\Workspace\Db\Space;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;

class UserGroup extends GroupsWorkspace {
	private IGroupManager $groupManager;

	public function __construct(IGroupManager $groupManager, IAppConfig $appConfig) {
		parent::__construct($appConfig);
		$this->groupManager = $groupManager;
	}

	public static function get(int $spaceId): string {
		return self::PREFIX_GID_USERS . $spaceId;
	}

	public static function getPrefix(): string {
		return self::PREFIX_GID_USERS;
	}

	public function create(Space $space): IGroup {
		$group = $this->groupManager->createGroup(self::PREFIX_GID_USERS . $space->getId());

		if (is_null($group)) {
			throw new CreateGroupException('Error to create a Space Manager group.', Http::STATUS_CONFLICT);
		}

		$group->setDisplayName(self::getDisplayPrefixUserGroup() . $space->getSpaceName());

		return $group;
	}
}
