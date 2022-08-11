<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2022 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Service;

use OCP\IUserManager;
use OCP\IGroupManager;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\UserService;
use OCP\IUser;
class GroupService {

    /** @var IGroupManager */
    private $groupManager;

    /** @var UserService */
    private $userService;

    public function __construct(IGroupManager $groupManager,
    UserService $userService)
    {
        $this->groupManager = $groupManager;
        $this->userService = $userService;
    }

    /** 
     * @param String[] $backends
     * @return String||null return the Group's backend (example : database or ldap) 
     * or null if it's not exist.
     */
    public function getTypeBackend($backends) {
        $backend = array_filter($backends, function($backend) {
            if (strtolower($backend) === "database" || strtolower($backend) === "ldap") {
                return $backend;
            }
            return null;
        });
        return $backend[0];
    }

    /**
     * @param String[] $backends
     * @return boolean return false if the backend is database or true if it's other.
     */
    public function checkLocked($backends) {
        $backend = $this->getTypeBackend($backends);

        if (strtolower($backend) !== 'database') {
            return true;
        }

        return false;
    }

    /**
     * @return array return a group associative array without the GE-, U-,
     * GeneralManager and WorkspacesManagers groups.
     */
    public function getAllFiltered() {
        $groups = $this->getAll();

        $groupsFiltered = array_values(array_filter($groups, function($group) {
            return $group['gid'] !== Application::GENERAL_MANAGER &&
            $group['gid'] !== Application::GROUP_WKSUSER &&
            $group['gid'] !== 'admin' &&
            preg_match('/^' . Application::GID_SPACE . Application::ESPACE_MANAGER_01 .'[0-9]/', $group['gid']) === 0 &&
            preg_match('/^'. Application::GID_SPACE . Application::ESPACE_USERS_01 .'[0-9]/', $group['gid']) === 0;
        }));

        return $groupsFiltered;

    }

    /**
     * @param $gid It's the GID for subgroup only
     * @return string
     */
    public function filterGIDToDisplayName($gid) {
        return str_replace(Application::GID_SPACE . Application::GID_SUBGROUP, "", $gid);
    }

    /**
     * @return array return a group associative array
     */
    public function getAll() {
        $groups = [];
        foreach($this->groupManager->search('') as $group) {
            $groups[] = [
                'gid'           => $group->getGID(),
                'displayName'   => $group->getDisplayName(),
                'is_locked'     => $this->checkLocked($group->getBackendNames()),
                'backend'       => $this->getTypeBackend($group->getBackendNames()),
                'users'         => $this->userService->formatUsersForGroups($group->getUsers(), $group->getGID()),
            ];
        }

        return $groups;
    }

    /**
     * @param $gid string
     * @return array
     * @todo delete
     */
    public function get($gid) {
        $group = $this->groupManager->get($gid);
        return [
            'gid'           => $group->getGID(),
            'displayName'   => $group->getDisplayName(),
            'is_locked'     => $this->checkLocked($group->getBackendNames()),
            'backend'       => $this->getTypeBackend($group->getBackendNames()),
        ];
    }

}