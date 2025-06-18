<?php

namespace OCA\Workspace\Migration;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\Group\GroupsWorkspace;
use OCA\Workspace\Upgrade\Upgrade;
use OCA\Workspace\Upgrade\UpgradeFixV300V301;
use OCP\AppFramework\Services\IAppConfig as ServiceIAppConfig;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class FixMigrationToV300 implements IRepairStep {

	public function __construct(
		private ServiceIAppConfig $appConfig,
		private UpgradeFixV300V301 $upgrade,
		private IAppConfig $appConfigManager,
		private LoggerInterface $logger
	) {
		$this->logger->debug('FixMigrationToV300 repair step initialised');

	}
	public function getName(): string {
		return 'Try to fix one problem to trigger the migration to v3.0.0 or 3.0.1';
	}

	public function run(IOutput $output): void {
		$versionInstalled = $this->appConfig->getAppValue('installed_version');
		$isBetween300And301 = version_compare($versionInstalled, '3.0.0', '>=') && version_compare($versionInstalled, '3.0.2', '<');
		if (!$isBetween300And301) {
			return;
		}

		if (!$this->appConfigManager->hasKey(Application::APP_ID, Upgrade::CONTROL_MIGRATION_V3)) {
			$this->appConfig->setAppValue(Upgrade::CONTROL_MIGRATION_V3, '0');
		}

		if (!$this->appConfigManager->hasKey(Application::APP_ID, 'DISPLAY_PREFIX_MANAGER_GROUP')
			&& !$this->appConfigManager->hasKey(Application::APP_ID, 'DISPLAY_PREFIX_USER_GROUP')) {
			$this->appConfig->setAppValue('DISPLAY_PREFIX_MANAGER_GROUP', GroupsWorkspace::DEFAULT_DISPLAY_PREFIX_MANAGER_GROUP);
			$this->appConfig->setAppValue('DISPLAY_PREFIX_USER_GROUP', GroupsWorkspace::DEFAULT_DISPLAY_PREFIX_USER_GROUP);
		}

		$this->upgrade->upgrade();
	}
}
