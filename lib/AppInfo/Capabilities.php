<?php

declare(strict_types=1);

namespace OCA\Workspace\AppInfo;

use OCP\App\IAppManager;
use OCP\Capabilities\ICapability;

class Capabilities implements ICapability
{
    public function __construct(private IAppManager $appManager)
    {
    }

    public function getCapabilities(): array
    {
        return [
            Application::APP_ID => [
                'version' => $this->appManager->getAppVersion(Application::APP_ID),
                'is_enabled' => $this->appManager->isEnabledForUser(Application::APP_ID)
            ]
        ];
    }
}
