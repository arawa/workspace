<?php
/**
 * @copyright 2021 Arawa <TODO>
 *
 * Repair step to create the group of user allowed to use the application
 * (that's: all general managers + all space managers)
 *
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 * @license <TODO>
 */

namespace OCA\Workspace\Migration;

use OCA\Workspace\AppInfo\Application;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RegisterWorkspaceUsersGroup implements IRepairStep {

	/** @var IConfig */
	private $config;

	/** @var IGroupManager */
	private $groupManager;

	public function __construct(IConfig $config,
		IGroupManager $groupManager) {

		$this->config = $config;
		$this->groupManager = $groupManager;
	}
	
	public function getName() {
		return 'Creates the group of user allowed to use the application';
	}

	public function run(IOutput $output) {
		// The group already exists when we upgrade the app
		if (!$this->groupManager->groupExists(Application::GROUP_WKSUSER)) {
			$this->groupManager->createGroup(Application::GROUP_WKSUSER);
		}
	}
}
