<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2025 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Controller;

use OCA\Workspace\Attribute\RequireExistingSpace;
use OCA\Workspace\Attribute\SpaceIdNumber;
use OCA\Workspace\Attribute\WorkspaceManagerRequired;
use OCA\Workspace\Service\Group\GroupsWorkspace;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\AppFramework\OCSController;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private IGroupManager $groupManager,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private SpaceManager $spaceManager,
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[FrontpageRoute(
		verb: 'DELETE',
		url: '/api/v1/space/{id}/group/{gid}',
		requirements: [
			'id' => '\d+',
			'gid' => '^SPACE-[A-Za-z0-9\W]+'
		]
	)]
	public function removeUsersFromGroup(int $id, string $gid, array $uids): Response {
		$users = [];
		$group = $this->groupManager->get($gid);

		$usersNotFound = [];
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			if (is_null($user)) {
				$usersNotFound[] = $uid;
			}

			$users[] = $user;
		}

		if (!empty($usersNotFound)) {
			$usersNotFound = array_map(fn ($uid) => "- {$uid}", $usersNotFound);
			$usersNotFound = implode("\n", $usersNotFound);
			throw new OCSNotFoundException("These users not exist in your Nextcloud instance:\n{$usersNotFound}");
		}

		$users = array_map(fn ($uid) => $this->userManager->get($uid), $uids);

		switch ($gid) {
			case GroupsWorkspace::isWorkspaceUserGroupId($gid):
				$space = $this->spaceManager->get($id);
				$this->spaceManager->removeUsersFromUserGroup($space, $group, $users);
				break;
			case GroupsWorkspace::isWorkspaceSubGroup($gid) || GroupsWorkspace::isWorkspaceGroup($group):
				$this->spaceManager->removeUsersFromSubGroup($group, $users);
				break;
			case GroupsWorkspace::isWorkspaceAdminGroupId($gid):
				$this->spaceManager->removeUsersFromWorkspaceManagerGroup($group, $users);
				break;
			default:
				throw new OCSBadRequestException("Your gid {$gid} doesn't come from a workspace");
		}

		$uids = implode(', ', $uids);
		$this->logger->info("Users are removed from groups (not added groups) in the workspace {$id}");
		$this->logger->info("Users are removed: {$uids}");

		return new DataResponse([], Http::STATUS_OK);
	}
}
