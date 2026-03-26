<?php

namespace OCA\Workspace\Migration;

use OCP\AppFramework\Services\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DisableAddedGroupRepairStep implements IRepairStep {

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	public function getName(): string {
		return 'Enable the connected group feature';
	}

	public function run(IOutput $output): void {
		// $this->appConfig->setAppValueBool('test_workspace', false);
		if ($this->appConfig->hasAppKey('added_group_disabled')) {
			return;
		}

		$this->appConfig->setAppValueBool('added_group_disabled', false);
	}
}
