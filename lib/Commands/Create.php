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
	public const OPTION_FORMAT_AVAILABLE = [ 'json' ];

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
				'format',
				'F',
				InputOption::VALUE_REQUIRED,
				'Output json',
				'json'
			)
			->addOption(
				'user-workspace-manager',
				'uwm',
				InputOption::VALUE_REQUIRED,
				'The user will be workspace manager of your workspace. Please, use its uid or email address'
			);

		parent::configure();
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {

		$outputMessage = '<info>success</info>';
		$spacename = $input->getArgument('name');

		if ($input->hasParameterOption('--user-workspace-manager')) {
			$pattern = $input->getOption('user-workspace-manager');
			if (!$this->userChecker->checkUserExist($pattern)) {
				throw new \Exception("The $pattern user or email is not exist.");
			}
		}

		if ($this->checkValueFormatOptionIsValid($input)) {
			throw new \Exception(
				sprintf(
					"The value is not valid.\nPlease, define an option valid : %s",
					implode(', ', self::OPTION_FORMAT_AVAILABLE)
				)
			);
		}

		$workspace = $this->spaceManager->create($spacename);

		if ($input->hasParameterOption('--user-workspace-manager')) {
			$userManagerName = $input->getOption('user-workspace-manager');
			$this->addUserToAdminGroupManager(
				$userManagerName,
				$workspace
			);
		}

		if ($input->hasParameterOption('--format')) {
			$value = $input->getOption('format');
			if (in_array($value, self::OPTION_FORMAT_AVAILABLE)) {
				$outputMessage = $this->formatOutput($input, $workspace);
			}
		}

		$output->writeln($outputMessage);

		return 0;
	}

	private function addUserToAdminGroupManager(string $username, array $workspace): bool {
		$user = $this->userFinder->findUser($username);
		$groupname = AdminGroupManager::findWorkspaceManager($workspace);
		$this->adminGroup->addUser($user, $groupname);

		return true;
	}

	private function checkValueFormatOptionIsValid(InputInterface $input): bool {
		if ($input->hasParameterOption('--format')) {
			$value = $input->getOption('format');
			if ($value !== 'json') {
				return true;
			}
		}

		return false;
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
