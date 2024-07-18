<?php

namespace OCA\Workspace\Group\Workspace;

use OCP\AppFramework\Services\IAppConfig;

class WorkspaceGroupsInfo {

	public function __construct(
		private IAppConfig $appConfig
	) {
	}

	public function getGeneralManagerGroup(): string {
		return $this->appConfig->getAppValue('general-manager-group', 'GeneralManager');
	}

	public function getWorkspacesManagersGroup(): string {
		return $this->appConfig->getAppValue('workspaces-managers-group', 'WorkspacesManagers');
	}
}
