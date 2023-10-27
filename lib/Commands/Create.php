<?php

namespace OCA\Workspace\Commands;

use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminGroupManager;
use OCA\Workspace\Space\SpaceManager;
use OCA\Workspace\User\UserFinder;
use OCA\Workspace\User\UserPresenceChecker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Create extends Command {

	public const OUTPUT_FORMAT_PLAIN = 'plain';
	public const OUTPUT_FORMAT_JSON_PRETTY = 'json_pretty';

	public function __construct(private SpaceManager $spaceManager,
		private AdminGroup $adminGroup,
		private UserPresenceChecker $userChecker,
		private UserFinder $userFinder) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				self::OUTPUT_FORMAT_PLAIN
			);

		$this
			->setName('workspace:create')
			->setDescription('This command allows you to create a workspace')
			->addArgument('name', InputArgument::REQUIRED, 'The name of your workspace.')
			->addOption(
				'user-workspace-manager',
				'uwm',
				InputOption::VALUE_REQUIRED,
				'The user will be workspace manager of your workspace. Please, use its uid or email address'
			);

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		$spacename = $input->getArgument('name');

		if ($input->hasParameterOption('--user-workspace-manager')) {
			$pattern = $input->getOption('user-workspace-manager');
			if (!$this->userChecker->checkUserExist($pattern)) {
				throw new \Exception("The $pattern user or email is not exist.");
			}
		}

		$workspace = $this->spaceManager->create($spacename);

		if ($input->hasParameterOption('--user-workspace-manager')) {
			$userManagerName = $input->getOption('user-workspace-manager');
			$this->addUserToAdminGroupManager(
				$userManagerName,
				$workspace
			);
		}

		$output->writeln($this->formatOutput($input, $workspace));

		return 0;
	}

	private function addUserToAdminGroupManager(string $username, array $workspace): bool {
		$user = $this->userFinder->findUser($username);
		$groupname = AdminGroupManager::findWorkspaceManager($workspace);
		$this->adminGroup->addUser($user, $groupname);

		return true;
	}

	private function formatOutput(InputInterface $input, array $items): string {
		switch ($input->getOption('output')) {
			case self::OUTPUT_FORMAT_JSON_PRETTY:
				return json_encode($items, JSON_PRETTY_PRINT);
				break;
			case self::OUTPUT_FORMAT_PLAIN:
				if (!is_array($items)) {
					return (string)$items;
				} else {
					return json_encode($items);
				}
				break;
			default:
				return json_encode($items);
				break;
		}
	}
}
