<?php

namespace OCA\Workspace\Migration;

use OCA\Workspace\AppInfo\Application;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class EnableConnectedGroupRepairStep implements IRepairStep {

	public function __construct(
		private IAppConfig $appConfig,
		private IAppConfig $appConfigManager,
	) {
	}

	public function getName(): string {
		return 'Enable the connected group feature';
	}

	public function run(IOutput $output): void {
		if (!$this->appConfig->hasAppKey(Application::APP_ID, 'connected_group_enabled')) {
			return;
		}

		$this->appConfigManager->setAppValueString('connected_group_enabled', 'true');
	}
}
