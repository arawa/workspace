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

use OCA\Workspace\ManagersWorkspace;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class RegisterWorkspaceUsersGroup implements IRepairStep {

	private IGroupManager $groupManager;
	private LoggerInterface $logger;

	public function __construct(IGroupManager $groupManager,
		LoggerInterface $logger) {
		$this->groupManager = $groupManager;
		$this->logger = $logger;

		$this->logger->debug('RegisterWorkspaceUsersGroup repair step initialised');
	}

	public function getName() {
		return 'Creates the group of user allowed to use the application';
	}

	public function run(IOutput $output) {
		// The group already exists when we upgrade the app
		if (!$this->groupManager->groupExists(ManagersWorkspace::WORKSPACES_MANAGERS)) {
			$this->logger->debug('Group ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' does not exist. Let\'s create it.');
			$this->groupManager->createGroup(ManagersWorkspace::WORKSPACES_MANAGERS);
		} else {
			$this->logger->debug('Group ' . ManagersWorkspace::WORKSPACES_MANAGERS . ' already exists. No need to create it.');
		}

		if (!$this->groupManager->groupExists(ManagersWorkspace::GENERAL_MANAGER)) {
			$this->logger->debug('Group ' . ManagersWorkspace::GENERAL_MANAGER . ' does not exist. Let\'s create it.');
			$this->groupManager->createGroup(ManagersWorkspace::GENERAL_MANAGER);
		} else {
			$this->logger->debug('Group ' . ManagersWorkspace::GENERAL_MANAGER . ' already exists. No need to create it.');
		}
	}
}
