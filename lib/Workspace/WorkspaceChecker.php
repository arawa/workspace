<?php

namespace OCA\Workspace\Workspace;

use OCA\Workspace\Service\Workspace\WorkspaceCheckService;

class WorkspaceChecker {
	public function __construct(
		private WorkspaceCheckService $workspaceCheckService
	) {
	}

	public function checkDuplicated(array $dataResponse): bool {
		$workspacesAreNotExist = [];
		$message = "";

		foreach ($dataResponse as $data) {
			if ($this->workspaceCheckService->isExist($data['workspace_name'])) {
				$workspacesAreNotExist[] = $data['workspace_name'];
			}
		}

		if (!empty($workspacesAreNotExist)) {
			$workspacesAreNotExist = array_map(fn ($spacename) => "  - $spacename\n", $workspacesAreNotExist);
			$message .= "The Workspace names below already exist:\n" . implode('', $workspacesAreNotExist);
			$message .= "\n";

			return $message;
		}
		
		return null;
	}
}
