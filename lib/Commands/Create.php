<?php

namespace OCA\Workspace\Commands;

use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminGroupManager;
use OCA\Workspace\Space\SpaceManager;
use OCA\Workspace\User\UserPresenceChecker;
use OCA\Workspace\User\UserSearcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command {
	public function __construct(private SpaceManager $spaceManager,
		private AdminGroup $adminGroup,
		private UserPresenceChecker $userChecker,
		private UserSearcher $userSearcher) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('workspace:create')
			->setDescription('This command allows you to create a workspace')
			->addArgument('name', InputArgument::REQUIRED, 'The name of your workspace.')
			->addOption('user-manager', 'um', InputOption::VALUE_REQUIRED, 'The user will be workspace manager of your workspace. Please, use its uid or email address');

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		$spacename = $input->getArgument('name');

		if ($input->hasParameterOption('--user-manager')) {
			$this->userChecker->checkUserExist(
				$input->getOption('user-manager')
			);
		}

		$workspace = $this->spaceManager->create($spacename);

		if ($input->hasParameterOption('--user-manager')) {
			$userManagerName = $input->getOption('user-manager');
			$this->addUserToAdminGroupManager(
				$userManagerName,
				$workspace
			);
		}

		return $workspace['id_space'];
	}

	private function addUserToAdminGroupManager(string $username, array $workspace): bool {
		$user = $this->userSearcher->searchUsers($username)[0];
		$groupname = AdminGroupManager::findWorkspaceManager($workspace);
		$this->adminGroup->addUser($user, $groupname);

		return true;
	}
}
