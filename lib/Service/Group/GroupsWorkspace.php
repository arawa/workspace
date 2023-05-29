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

use OCA\Workspace\CreateGroupException;
use OCA\Workspace\Db\Space;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;

class GroupsWorkspace {
    
	public function __construct(private IAppConfig $appConfig,
        private IGroupManager $groupManager) {
	}

	/**
	 * Use the OCA\Workspace\Db\Space to get its spaceId and spaceName.
	 */
    public function create(IGroupWorkspace $groupWorkspace, Space $space): IGroup {
    	$group = $this->groupManager->createGroup($groupWorkspace->getGidPrefix() . $space->getId());

    	if (is_null($group)) {
    		throw new CreateGroupException('Error to create a Space Manager group.', Http::STATUS_CONFLICT);
    	}

    	$group->setDisplayName($groupWorkspace->getDisplayPrefix() . $space->getSpaceName());

    	return $group;
    }

}
