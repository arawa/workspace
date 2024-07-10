<?php

namespace OCA\Workspace\Migration;

use OCA\Workspace\Upgrade\Upgrade;
use OCA\Workspace\Upgrade\UpgradeV300;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ReRunChangeGroupnamesForPgSql implements IRepairStep {
	public function __construct(
		private IConfig $config,
		private IAppConfig $appConfig,
		private UpgradeV300 $upgrade,
	) {
	}

	public function getName(): string {
		return 'Rerun the change groupnames repair step for a Nextcloud instance using PostgreSQL.';
	}

	public function run(IOutput $output): void {
		$sgbdName = $this->config->getSystemValue('dbtype');

		if ($sgbdName !== 'pgsql') {
			return;
		}

		$statusMigration = boolval($this->appConfig->getAppValue(Upgrade::CONTROL_MIGRATION_V3, '1'));

		if ($statusMigration === true) {
			return;
		}

		$this->upgrade->upgrade();
	}
}
