<?php

/**
 * @copyright Copyright (c) 2025 Arawa
 *
 * @author 2025 Sebastien Marinier <sebastien.marinier@arawa.fr>
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

/**
 * Check the database for workspace consistency
 *
 * Usage: php scripts/database_checker.php -h to see the help
 */

define('OC_CONSOLE', 1);

require_once __DIR__ . '/../../../lib/base.php';

use OC\SystemConfig;
use OCA\GroupFolders\Folder\FolderManager;
use OCA\Workspace\Db\SpaceMapper;

// workspace
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\User\UserGroup;
use OCP\IDBConnection;

// GroupFolders
use OCP\IGroupManager;

function exceptionHandler($exception) {
	echo 'An unhandled exception has been thrown:' . PHP_EOL;
	echo $exception;
	exit(1);
}

class WorkSpaceChecker {

	private array $spaces;
	private array $groupFolders;
	private array $groupFoldersGroups;
	private array $groups;

	public function __construct(
		private IDBConnection $dbConnection,
		private SpaceMapper $spaceMapper,
		private FolderManager $folderManager,
		private IGroupManager $groupManager
	) {
		$this->spaces = $spaceMapper->findAll();
		$this->groupFolders = $folderManager->getAllFolders();
		$this->loadGroupFoldersGroups();
		$this->loadGroups();
	}

	private function loadGroupFoldersGroups() {
		$query = $this->dbConnection->getQueryBuilder();
		$query->select('g.folder_id', 'g.group_id', 'g.circle_id', 'g.permissions')
			->from('group_folders_groups', 'g');
		$this->groupFoldersGroups = $query->executeQuery()->fetchAll();
	}

	private function loadGroups() {
		$groups = $this->groupManager->search('');
		$this->groups = [];
		foreach ($groups as $group) {
			$this->groups[$group->getGID()] = $group;
		}
	}

	private static function separator() {
		return '------------------------------------' . PHP_EOL;
	}

	public function counts() {
		echo self::separator();
		echo 'Workspaces: ' . count($this->spaces) . PHP_EOL;
		echo 'GroupFolders: ' . count($this->groupFolders) . PHP_EOL;
		echo 'GroupFoldersGroups: ' . count($this->groupFoldersGroups) . PHP_EOL;
		echo 'Groups: ' . count($this->groups) . PHP_EOL;
	}

	/**
	 * Check if all groupfolders linked to a workspace exists
	 */
	public function checkSpaces() {
		echo self::separator();
		echo 'Checking spaces...' . PHP_EOL;
		foreach ($this->spaces as $space) {
			$gfId = $space->getGroupfolderId();
			if (!isset($this->groupFolders[$gfId])) {
				echo "** GroupFolder $gfId not found for workspace " . $space->getSpaceId() . ' (' . $space->getSpaceName() . ')' . PHP_EOL;
				continue;
			}
		}
	}

	/**
	 * Check if all groupfolders are linked to a workspace
	 */
	public function checkGroupFolders() {
		echo self::separator();
		echo 'Checking groupfolders...' . PHP_EOL;
		foreach ($this->groupFolders as $groupFolder) {
			// retreive the workspace linked to the groupfolder
			$space = $this->findSpaceByGroupFolderId($groupFolder['id']);
			if ($space === null) {
				echo '** GroupFolder ' . $groupFolder['id'] . ' (' . $groupFolder['mount_point'] . ') not found as workspace' . PHP_EOL;
			}
			$hasUGroup = false;
			$hasGEGroup = false;
			foreach ($groupFolder['groups'] as $gid => $group) {
				// check if the group exists
				if (!isset($this->groups[$gid])) {
					echo '** Group ' . $gid . ' not found for groupfolder ' . $groupFolder['id'] . ' (' . $groupFolder['mount_point'] . ')' . PHP_EOL;
				}
				if (str_starts_with($gid, UserGroup::GID_PREFIX)) {
					$hasUGroup = true;
				} elseif (str_starts_with($gid, AdminGroup::GID_PREFIX)) {
					$hasGEGroup = true;
				}
			}
			if ($space != null) {
				$msg = ' group not found for groupfolder ' . $groupFolder['id'] . ' (' . $groupFolder['mount_point'] . ') but the workspace exists (' . $space->getSpaceName() . ')';
				if (!$hasUGroup) {
					echo '** WS User' . $msg . PHP_EOL;
				}
				if (!$hasGEGroup) {
					echo '** WS Admin' . $msg . PHP_EOL;
				}
			}
		}
	}

	/**
	 * Check if all groupfolders groups exists, and looking like workspace groups, are linked to a workspace
	 */
	public function checkGroupFoldersGroups() {
		echo self::separator();
		echo 'Checking groupfolders groups...' . PHP_EOL;
		foreach ($this->groupFoldersGroups as $groupFolderGroup) {
			$gfId = $groupFolderGroup['folder_id'];
			$groupId = $groupFolderGroup['group_id'];
			if (!isset($this->groupFolders[$gfId])) {
				$groupFolder = null;
				$groupName = isset($this->groups[$groupId]) ? ' (' . $this->groups[$groupId]->getDisplayName() . ')' : '';
				echo "** GroupFolder $gfId not found for group id " . $groupId . $groupName . PHP_EOL;
			} else {
				$groupFolder = $this->groupFolders[$gfId];
			}
			if (!isset($this->groups[$groupId])) {
				$groupFolderName = $groupFolder ? ' (' . $groupFolder['mount_point'] . ')' : '';
				echo "** Group $groupId not found for groupfolder id " . $gfId . $groupFolderName . PHP_EOL;
			}
		}
	}

	/**
	 * Check if all groups, looking like workspace groups, are linked to a workspace
	 */
	public function checkGroups() {
		echo self::separator();
		echo 'Checking groups...' . PHP_EOL;
		foreach ($this->groups as $group) {
			if (!str_starts_with($group->getGID(), 'SPACE-')) {
				continue; // not interested in
			}
			$space = $this->findSpaceByGroupId($group->getGID());
			if ($space === null) {
				echo '** Group ' . $group->getGID() . ' (' . $group->getDisplayName() . ') not found as workspace' . PHP_EOL;
			}
			$groupFolderId = $this->findGroupFolderIdByGroupId($group->getGID());
			if ($groupFolderId === null) {
				echo '** Group ' . $group->getGID() . ' (' . $group->getDisplayName() . ') not found as group from groupfolder' . PHP_EOL;
			} elseif ($space === null) {
				echo '... but declared as group from groupfolder id ' . $groupFolderId;
				if (isset($this->groupFolders[$groupFolderId])) {
					$groupFolder = $this->groupFolders[$groupFolderId];
					echo ' (' . $groupFolder['mount_point'] . ')' . PHP_EOL;
				} else {
					echo '... and this groupfolder doesn\'t exist' . PHP_EOL;
				}
			}
		}
	}

	private function findSpaceByGroupFolderId($groupFolderId) {
		foreach ($this->spaces as $space) {
			if ($space->getGroupfolderId() == $groupFolderId) {
				return $space;
			}
		}
		return null;
	}

	private function findSpaceByGroupId($groupId) {
		$last = strrpos($groupId, '-');
		if ($last === false) {
			return null;
		}
		$spaceId = intval(substr($groupId, $last + 1));

		foreach ($this->spaces as $space) {
			if ($space->getSpaceId() == $spaceId) {
				return $space;
			}
		}
		return null;
	}

	private function findGroupFolderIdByGroupId($groupId) {
		foreach ($this->groupFoldersGroups as $groupFolderGroup) {
			$gfId = $groupFolderGroup['folder_id'];
			$gfGroupId = $groupFolderGroup['group_id'];
			if ($gfGroupId == $groupId) {
				return $gfId;
			}
		}
		return null;
	}
}

class MyConfig extends SystemConfig {
	public function __construct(
		private SystemConfig $config,
		private array $params
	) {
	}

	public function getValue($key, $default = null) {
		if (isset($this->params[$key])) {
			return $this->params[$key];
		}
		return $this->config->getValue($key, $default);
	}
}

function printUsage() {
	echo "Usage: php scripts/database_checker.php [options]\n";
	echo "Options:\n";
	echo "  -h, --help       Show this help\n";
	echo "  -v, --verbose    Verbose mode\n";
	echo "  --dbhost         Database host (localhost)\n";
	echo "  --dbuser         Database user (root)\n";
	echo "  --dbpassword     Database user password ()\n";
	echo "  --dbname         Database name (default: use local nextcloud config)\n";
	echo "  --dbtype         Database type (mysql, pgsql)\n";
}

$verbose = false;
$dbConfig = [
	'dbtype' => 'mysql',
	'mysql.utf8mb4' => true,
	'dbhost' => 'localhost',
	'dbuser' => 'root',
	'dbpassword' => '',
	'dbname' => ''
];
$error = 0;

if ($argc > 1) {
	for ($i = 1; $i < $argc; $i++) {
		switch ($argv[$i]) {
			case '-h':
			case '--help':
				printUsage();
				exit(0);
			case '-v':
			case '--verbose':
				$verbose = true;
				break;
			case '--host':
			case '--dbhost':
				$dbConfig['dbhost'] = $argv[++$i];
				break;
			case '--user':
			case '--dbuser':
				$dbConfig['dbuser'] = $argv[++$i];
				break;
			case '--password':
			case '--dbpassword':
				$dbConfig['dbpassword'] = $argv[++$i];
				break;
			case '--dbname':
				$dbConfig['dbname'] = $argv[++$i];
				break;
			case '--dbtype':
				$dbConfig['dbtype'] = $argv[++$i];
				break;
			default:
				echo "Unkown argument $i: " . $argv[$i] . "\n";
				$error++;
				break;
		}
	}
}

if ($error) {
	printUsage();
	exit(1);
}

$isExternal = !empty($dbConfig['dbname']);

if ($isExternal) {
	$dbConnection = OC::$server->get(IDBConnection::class);
	$dbConnection->close();

	if ($verbose) {
		echo "Connecting to an external database...\n";
	}

	$systemConfig = OC::$server->get(SystemConfig::class);
	$myConfig = new MyConfig($systemConfig, $dbConfig);
	$factory = new \OC\DB\ConnectionFactory($myConfig);
	$type = $myConfig->getValue('dbtype', 'mysql');
	if (!$factory->isValidType($type)) {
		throw new \OC\DatabaseException('Invalid database type');
	}
	$dbConnection = $factory->getConnection($type, []);

	\OC::$server->registerService(\OC\DB\ConnectionAdapter::class,
		function ($c) use ($dbConnection) {
			return new \OC\DB\ConnectionAdapter(
				$dbConnection
			);
		}
	);
} else {

	if ($verbose) {
		echo "Connecting to local Nextcloud base...\n";
	}
}

set_exception_handler('exceptionHandler');
\OC_App::loadApp('workspace');

$wsCheccker = OC::$server->get(WorkSpaceChecker::class);
$wsCheccker->counts();
$wsCheccker->checkSpaces();
$wsCheccker->checkGroupFolders();
$wsCheccker->checkGroupFoldersGroups();
$wsCheccker->checkGroups();
