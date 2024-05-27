<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Workspace\Commands;

use OCA\Workspace\Files\MassiveWorkspaceCreation\Csv;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminGroupManager;
use OCA\Workspace\Group\User\UserGroup;
use OCA\Workspace\Group\User\UserGroupManager;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Space\SpaceManager;
use OCA\Workspace\User\UserFinder;
use OCA\Workspace\User\UserPresenceChecker;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Import extends Command {

	public function __construct(
		private AdminGroup $adminGroup,
		private Csv $csvCreatingWorkspaces,
		private GroupfolderHelper $groupfolderHelper,
		private IGroupManager $groupManager,
		private IRequest $request,
		private IUserManager $userManager,
		private SpaceManager $spaceManager,
		private UserFinder $userFinder,
		private UserGroup $userGroup,
		private UserPresenceChecker $userChecker,
		private WorkspaceCheckService $workspaceCheckService,
		private LoggerInterface $logger) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$path = realpath($input->getArgument('path'));
	  
		if (!$this->csvCreatingWorkspaces->isCsvFile($path)) {
			$this->logger->critical("It's not a csv file. Your file is a " . (string)$this->csvCreatingWorkspaces->getMimeType($path) . " mimetype.");
			throw new \Exception("It's not a csv file. Your file is a " . (string)$this->csvCreatingWorkspaces->getMimeType($path) . " mimetype.");
		}

		if (!$this->csvCreatingWorkspaces->hasProperHeader($path)) {
			$this->logger->error(sprintf(
				"No respect the glossary headers. "
				. "Please, you must define these 2 headers : "
				. "%s : To specify the workspace name."
				. "%s : To specify the user's user-id or email address."
				. "%s : To specify the workspace quota.",
				implode(", ", Csv::WORKSPACE_FIELD),
				implode(", ", Csv::USER_FIELD),
				implode(", ", Csv::QUOTA_FIELD)
			));
			throw new \Exception(
				sprintf(
					"No respect the glossary headers.\n\n"
					. "Please, you must define these 2 headers :\n"
					. "     - %s : To specify the workspace name.\n"
					. "     - %s : To specify the user's user-id or email address.\n"
					. "     - %s : To specify the workspace quota.",
					implode(", ", Csv::WORKSPACE_FIELD),
					implode(", ", Csv::USER_FIELD),
					implode(", ", Csv::QUOTA_FIELD)
				)
			);
		}

		$dataFormated = $this->csvCreatingWorkspaces->parser($path);

		foreach ($dataFormated as $data) {
			if ($this->checkIllimitedQuota($data['quota'])) {
				continue;
			}

			preg_match('/[a-zA-Z].*/', $data['quota'], $matches);
			$unit = strtolower($matches[0]);
			
			if (!$this->checkUnitBytes($unit)) {
				throw new \Exception('You didn\'t define the good unit for quota. Allowed units are: kb, mb, gb or tb');
			}
		}

		$spacenamesWithCharacterSpecials = $this->getWorkspacesWithCharacterSpecials($dataFormated);

		if (!is_null($spacenamesWithCharacterSpecials)) {
			$this->logger->error($spacenamesWithCharacterSpecials);
			throw new \Exception($spacenamesWithCharacterSpecials);
		}

		if ($this->workspaceCheckService->spacenamesIsDuplicated($dataFormated)) {
			$message = "Impossible to import your workspaces from the csv file.\n";
			$message .= $this->getSpacenamesFromCsvFileDuplicated($dataFormated);
			throw new \Exception($message);
		}

		$message = $this->getSpacenamesDuplicated($dataFormated);
		$message .= $this->getUsersArentExist($dataFormated);

		if (!empty($message)) {
			$this->logger->error($message);
			throw new \Exception($message);
		}

		foreach ($dataFormated as $data) {

			$user = $this->userFinder->findUser($data['user_uid']);

			$workspace = $this->spaceManager->create($data['workspace_name']);
			$adminGroupname = AdminGroupManager::findWorkspaceManager($workspace);
			$userGroupname = UserGroupManager::findWorkspaceManager($workspace);

			$quota = $this->convertToByte($data['quota']);
			$this->groupfolderHelper->setFolderQuota($workspace['folder_id'], $quota);

			$this->adminGroup->addUser($user, $adminGroupname);
			$this->userGroup->addUser($user, $userGroupname);
		}

		$this->logger->info("workspaces import done");
		$output->writeln("<info>workspaces import done</info>");

		return 0;
	}

	protected function configure(): void {
		$this
			->setName('workspace:import')
			->setDescription('This command allows you to import a csv file to create workspaces and define their managers.')
			->addArgument('path', InputArgument::REQUIRED, 'The path of the csv file.');
		parent::configure();
	}

	private function checkUnitBytes(string $unit): bool {
		$unit = strtolower($unit);

		$units = [ 'kb', 'mb', 'gb', 'tb'];

		if (in_array($unit, $units)) {
			return true;
		}

		return false;
	}

	private function checkIllimitedQuota(string $unit): bool {
		if ($unit === '-3') {
			return true;
		}

		return false;
	}

	private function convertToByte(string $value): int {

		if ($this->checkIllimitedQuota($value)) {
			return -3;
		}

		preg_match('/[0-9]+/', $value, $matches);
		$valueOfUnit = (int)$matches[0];

		preg_match('/[a-zA-Z].*/', $value, $matches);
		$unit = strtolower($matches[0]);

		switch ($unit) {
			case 'kb':
				$valueInByte = $valueOfUnit * 1024;
				break;
			case 'mb':
				$valueInByte = $valueOfUnit * 1024 ** 2;
				break;
			case 'gb':
				$valueInByte = $valueOfUnit * 1024 ** 3;
				break;
			case 'tb':
				$valueInByte = $valueOfUnit * 1024 ** 4;
				break;
		}

		return $valueInByte;
	}

	private function getSpacenamesFromCsvFileDuplicated(array $spaces): string {
		$workspaceNames = [];
		$message = '';

		foreach ($spaces as $space) {
			$workspaceNames[] = $space['workspace_name'];
		}

		$workspaceNamesDiff = array_values(
			array_diff_assoc($workspaceNames, array_unique($workspaceNames))
		);
		
		$spacenamesFormated = array_map(fn ($spacename) => "- $spacename\n", $workspaceNamesDiff);

		$message .= "The Workspace names below are duplicated:\n" . implode('', $spacenamesFormated);

		return $message;
	}
	
	private function getSpacenamesDuplicated(array $dataResponse): ?string {
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

	private function getUsersArentExist(array $dataResponse): ?string {
		$usersAreNotExist = [];
		$message = "";

		foreach ($dataResponse as $data) {
			if (!$this->userChecker->checkUserExist($data['user_uid'])) {
				$usersAreNotExist[] = $data['user_uid'];
			}
		}

		if (!empty($usersAreNotExist)) {
			$usersAreNotExist = array_map(fn ($username) => "  - $username\n", $usersAreNotExist);
			$message .= "The below users do not exist:\n" . implode('', $usersAreNotExist);

			return $message;
		}

		return null;
	}

	private function getWorkspacesWithCharacterSpecials(array $dataResponse): ?string {
		$spacenamesWithCharacterSpecials = [];
		$message = "";

		foreach ($dataResponse as $data) {
			if ($this->workspaceCheckService->containSpecialChar($data['workspace_name'])) {
				$spacenamesWithCharacterSpecials[] = $data['workspace_name'];
			}
		}

		if (!empty($spacenamesWithCharacterSpecials)) {
			$spacenamesStringify = array_map(fn ($spacename) => "   - $spacename\n", $spacenamesWithCharacterSpecials);
			$message .= "The below workspace names contain special characters :\n" . implode('', $spacenamesStringify);
			$message .= "\nPlease, make sure the Workspace names do not contain one of the following characters: " . implode(" ", str_split(WorkspaceCheckService::CHARACTERS_SPECIAL));
			
			return $message;
		}
		
		return null;
	}
}
