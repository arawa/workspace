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

namespace OCA\Workspace\Migration;

use OCA\Workspace\Group\Workspace\WorkspaceGroupsInfo;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class RegisterWorkspaceUsersGroup implements IRepairStep {
	public function __construct(private IGroupManager $groupManager,
		private LoggerInterface $logger,
		private WorkspaceGroupsInfo $groupInfo) {
		$this->logger->debug('RegisterWorkspaceUsersGroup repair step initialised');
	}

	public function getName(): string {
		return 'Creates the group of user allowed to use the application';
	}

	public function run(IOutput $output): void {
		$workspacesManagersGroupname = $this->groupInfo->getWorkspacesManagersGroup();
		$generalManagerGroupname = $this->groupInfo->getGeneralManagerGroup();

		// The group already exists when we upgrade the app
		if (!$this->groupManager->groupExists($workspacesManagersGroupname)) {
			$this->logger->debug('Group ' . $workspacesManagersGroupname . ' does not exist. Let\'s create it.');
			$this->groupManager->createGroup($workspacesManagersGroupname);
		} else {
			$this->logger->debug('Group ' . $workspacesManagersGroupname . ' already exists. No need to create it.');
		}

		if (!$this->groupManager->groupExists($generalManagerGroupname)) {
			$this->logger->debug('Group ' . $generalManagerGroupname . ' does not exist. Let\'s create it.');
			$this->groupManager->createGroup($generalManagerGroupname);
		} else {
			$this->logger->debug('Group ' . $generalManagerGroupname . ' already exists. No need to create it.');
		}
	}
}
