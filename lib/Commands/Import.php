<?php

namespace OCA\Workspace\Commands;

use OCP\IRequest;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCA\Workspace\User\UserFinder;
use OCA\Workspace\Space\SpaceManager;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\User\UserPresenceChecker;
use Symfony\Component\Console\Command\Command;
use OCA\Workspace\Group\Admin\AdminGroupManager;
use OCA\Workspace\Files\CsvMassCreatingWorkspaces;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;

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

        foreach ($dataFormated as $data) {
            $this->workspaceCheckService->isExist($data['workspace_name']);
            $this->userChecker->checkUserExist($data['user_uid']);
        }

		foreach ($dataFormated as $data) {

			$user = $this->userFinder->findUser($data['user_uid']);

			$workspace = $this->spaceManager->create($data['workspace_name']);
			$groupname = AdminGroupManager::findWorkspaceManager($workspace);
			$this->adminGroup->addUser($user, $groupname);
		}

		return 0;
	}

	protected function configure(): void {
		$this
			->setName('workspace:import')
			->setDescription('This command allows you to import a csv file to create workspaces and define the workspace manager users.')
			->addArgument('path', InputArgument::REQUIRED, 'The path of the csv file.');
		parent::configure();
	}
}
