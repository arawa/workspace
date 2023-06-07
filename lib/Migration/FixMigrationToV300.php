<?php

namespace OCA\Workspace\Migration;

use OCP\IAppConfig;
use OCP\Migration\IOutput;
use Psr\Log\LoggerInterface;
use OCP\Migration\IRepairStep;
use OCA\Workspace\Upgrade\Upgrade;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Upgrade\UpgradeV300;
use OCP\AppFramework\Services\IAppConfig as ServiceIAppConfig;

class FixMigrationToV300 implements IRepairStep
{

    public function __construct(private ServiceIAppConfig $appConfig,
        private UpgradeV300 $upgrade,
        private IAppConfig $appConfigManager,
        private LoggerInterface $logger)
    {
		$this->logger->debug('FixMigrationToV300 repair step initialised');

    }
    public function getName(): string
    {
        return 'Try to fix one problem to trigger the migration to v3.0.0 or 3.0.1';
    }

    public function run(IOutput $output): void
    {
        $versionInstalled = $this->appConfig->getAppValue('installed_version');
        $isBetween300And301 = version_compare($versionInstalled, '3.0.0', '>=') && version_compare($versionInstalled, '3.0.2', '<');
        if (!$isBetween300And301) {
            $this->appConfig->setAppValue('test', 'restopped');
            return;
        }

        if (!$this->appConfigManager->hasKey(Application::APP_ID, Upgrade::CONTROL_MIGRATION_V3)) {
            $this->appConfig->setAppValue(Upgrade::CONTROL_MIGRATION_V3, '0');
        }

		if (!$this->appConfigManager->hasKey(Application::APP_ID, 'DISPLAY_PREFIX_MANAGER_GROUP')
			&& !$this->appConfigManager->hasKey(Application::APP_ID, 'DISPLAY_PREFIX_USER_GROUP')) {
			$this->appConfig->setAppValue('DISPLAY_PREFIX_MANAGER_GROUP', 'WM-');
			$this->appConfig->setAppValue('DISPLAY_PREFIX_USER_GROUP', 'U-');
		}

        $this->upgrade->upgrade();
    }
}
