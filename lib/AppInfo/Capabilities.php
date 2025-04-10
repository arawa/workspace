<?php

declare(strict_types=1);

namespace OCA\Workspace\AppInfo;

use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;
use OCP\IAppConfig;
use OCP\IConfig;

class Capabilities implements ICapability {
	public function __construct(
		private IAppManager $appManager,
		private IAppConfig $appConfigManager,
		private IConfig $config,
	) {
	}

	public function getCapabilities(): array {
		return [
			Application::APP_ID => [
				'version' => $this->appManager->getAppVersion(Application::APP_ID),
				'is_enabled' => $this->appManager->isEnabledForUser(Application::APP_ID),
				'groupfolders_version' => $this->appManager->getAppVersion('groupfolders'),
				'groupfolders_acl-inherit-per-user' => $this->config->getAppValue('groupfolders', 'acl-inherit-per-user', 'false') === 'true'
			]
		];
	}
}
