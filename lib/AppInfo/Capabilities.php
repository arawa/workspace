<?php

declare(strict_types=1);

namespace OCA\Workspace\AppInfo;

use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;
use OCP\IAppConfig;

class Capabilities implements ICapability
{
    public function __construct(
        private IAppManager $appManager,
        private IAppConfig $appConfigManager
    ) {
    }

    public function getCapabilities(): array
    {
        return [
            Application::APP_ID => [
                'version' => $this->appManager->getAppVersion(Application::APP_ID),
                'is_enabled' => $this->appManager->isEnabledForUser(Application::APP_ID),
                'groupfolders_version' => $this->appManager->getAppVersion('groupfolders'),
            ]
        ];
    }
}
