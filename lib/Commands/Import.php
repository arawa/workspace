<?php

namespace OCA\Workspace\Commands;

use OCA\Workspace\Files\CsvMassCreatingWorkspaces;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminGroupManager;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Space\SpaceManager;
use OCA\Workspace\User\UserFinder;
use OCA\Workspace\User\UserPresenceChecker;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Import extends Command {

	public function __construct(
		private IGroupManager $groupManager,
		private IRequest $request,
		private IUserManager $userManager,
		private CsvMassCreatingWorkspaces $csvCreatingWorkspaces,
		private SpaceManager $spaceManager,
		private AdminGroup $adminGroup,
		private UserPresenceChecker $userChecker,
		private UserFinder $userFinder,
		private WorkspaceCheckService $workspaceCheckService) {
		parent::__construct();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$path = realpath($input->getArgument('path'));
	  
		if (!$this->csvCreatingWorkspaces->isCsvFile($path)) {
			throw new \Exception("It's not a csv file. Your file is a " . (string)$this->csvCreatingWorkspaces->getMimeType($path) . " mimetype.");
		}

		if (!$this->csvCreatingWorkspaces->hasProperHeader($path)) {
			throw new \Exception('No respect the glossary headers.');
		}

		$dataFormated = $this->csvCreatingWorkspaces->parser($path);

		$message = $this->getSpacenamesDuplicated($dataFormated);
		$message .= $this->getUsersArentExist($dataFormated);

		if (!empty($message)) {
			throw new \Exception($message);
		}

		foreach ($dataFormated as $data) {

			$user = $this->userFinder->findUser($data['user_uid']);

			$workspace = $this->spaceManager->create($data['workspace_name']);
			$groupname = AdminGroupManager::findWorkspaceManager($workspace);
			$this->adminGroup->addUser($user, $groupname);
		}

		$output->writeln("The import is done.");

		return 0;
	}

	protected function configure(): void {
		$this
			->setName('workspace:import')
			->setDescription('This command allows you to import a csv file to create workspaces and define the workspace manager users.')
			->addArgument('path', InputArgument::REQUIRED, 'The path of the csv file.');
		parent::configure();
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
			$message .= "Workspace names below already exist :\n" . implode('', $workspacesAreNotExist);
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
			$message .= "Users below aren't known :\n" . implode('', $usersAreNotExist);

			return $message;
		}

		return null;
	}
}
