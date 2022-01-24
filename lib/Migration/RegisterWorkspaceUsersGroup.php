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

use OCA\Workspace\AppInfo\Application;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RegisterWorkspaceUsersGroup implements IRepairStep {

	/** @var IConfig */
	private $config;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ILogger */
	private $logger;

	public function __construct(IConfig $config,
		IGroupManager $groupManager,
		ILogger $logger) {

		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->logger = $logger;

		$this->logger->debug('RegisterWorkspaceUsersGroup repair step initialised');
	}
	
	public function getName() {
		return 'Creates the group of user allowed to use the application';
	}

	public function run(IOutput $output) {
		// The group already exists when we upgrade the app
		if (!$this->groupManager->groupExists(Application::GROUP_WKSUSER)) {
			$this->logger->debug('Group ' . Application::GROUP_WKSUSER . ' does not exist. Let\'s create it.');
			$this->groupManager->createGroup(Application::GROUP_WKSUSER);
		} else {
			$this->logger->debug('Group ' . Application::GROUP_WKSUSER . ' already exists. No need to create it.');
		}

		if(!$this->groupManager->groupExists(Application::GENERAL_MANAGER)) {
			$this->logger->debug('Group ' . Application::GENERAL_MANAGER . ' does not exist. Let\'s create it.');
			$this->groupManager->createGroup(Application::GENERAL_MANAGER);
		} else {
			$this->logger->debug('Group ' . Application::GENERAL_MANAGER . ' already exists. No need to create it.');
		}
	}
}
