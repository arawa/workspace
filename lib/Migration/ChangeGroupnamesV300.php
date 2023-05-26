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

namespace OCA\Workspace\Migration;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Upgrade\Upgrade;
use OCA\Workspace\Upgrade\UpgradeV300;
use OCP\AppFramework\Services\IAppConfig as ServicesIAppConfig;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class ChangeGroupnamesV300 implements IRepairStep {
	public function __construct(private LoggerInterface $logger,
		private IAppConfig $appConfigManager,
		private ServicesIAppConfig $appConfig,
		private UpgradeV300 $upgradeV300) {
		$this->logger->debug('RegisterWorkspaceUsersGroup repair step initialised');
	}

	public function getName(): string {
		return 'Creates the group of user allowed to use the application';
	}

	public function run(IOutput $output): void {
		if (!$this->appConfigManager->hasKey(Application::APP_ID, 'DISPLAY_PREFIX_MANAGER_GROUP')
			&& !$this->appConfigManager->hasKey(Application::APP_ID, 'DISPLAY_PREFIX_USER_GROUP')) {
			$this->appConfig->setAppValue('DISPLAY_PREFIX_MANAGER_GROUP', 'WM-');
			$this->appConfig->setAppValue('DISPLAY_PREFIX_USER_GROUP', 'U-');
		}

		if (!$this->appConfigManager->hasKey(Application::APP_ID, Upgrade::CONTROL_MIGRATION_V3)) {
			$this->appConfig->setAppValue(Upgrade::CONTROL_MIGRATION_V3, '0');
		}

		$versionString = $this->appConfig->getAppValue('installed_version');
		$versionSplitted = explode('.', $versionString);
		$version = intval(implode('', $versionSplitted));
   
		$controlMigration = boolval($this->appConfig->getAppValue(Upgrade::CONTROL_MIGRATION_V3));


		if ($version <= Application::V300 && $controlMigration === false) {
			$this->upgradeV300->upgrade();
		}
	}
}
