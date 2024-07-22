<?php

namespace OCA\Workspace\Migration;

use OCP\AppFramework\Services\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use Psr\Log\LoggerInterface;

class RegisterGroupsStep implements IRepairStep {
	public function __construct(
		private IAppConfig $appConfig,
		private LoggerInterface $logger
	) {
	}

	public function getName(): string {
		return 'Register WorkspacesManagers and GeneralManager in the app_config table.';
	}

	public function run(IOutput $output): void {
		$generalManagerGroup = $this->appConfig
			->getAppValue('general-manager-group', '')
		;

		$workspacesManagersGroup = $this->appConfig
			->getAppValue('workspaces-managers-group', '')
		;

		if (empty($generalManagerGroup)) {
			$this->appConfig
				->setAppValue('general-manager-group', 'GeneralManager')
			;
		}

		if (empty($workspacesManagersGroup)) {
			$this->appConfig
				->setAppValue('workspaces-managers-group', 'WorkspacesManagers')
			;
		}
	}
}
