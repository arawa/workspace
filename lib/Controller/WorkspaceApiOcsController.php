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
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\Group\UserGroup;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSBadRequestException;
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
		private SpaceMapper $spaceMapper,
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	#[WorkspaceManagerRequired]
	#[RequireExistingSpace]
	#[SpaceIdNumber]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'POST',
		url: '/api/v1/space/{id}/users',
		requirements: ['id' => '\d+']
	)]
	public function addUsersInWorkspace(int $id, array $uids): Response {

		$types = array_unique(array_map(fn ($uid) => gettype($uid), $uids));
		$othersStringTypes = array_values(array_filter($types, fn ($type) => $type !== 'string'));

		if (!empty($othersStringTypes)) {
			throw new OCSBadRequestException('uids params must contain a string array only');
		}

		$usersNotExist = [];
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			if (is_null($user)) {
				$usersNotExist[] = $uid;
			}
		}

		if (!empty($usersNotExist)) {
			$formattedUsers = implode(array_map(fn ($user) => "- {$user}" . PHP_EOL, $usersNotExist));
			$this->logger->error('These users not exist in your Nextcoud instance : ' . PHP_EOL . $formattedUsers);
			throw new OCSBadRequestException('These users not exist in your Nextcoud instance : ' . PHP_EOL . $formattedUsers);
		}

		$space = $this->spaceMapper->find($id);

		if (is_null($space)) {
			$this->logger->error("The workspace with {$id} id doesn't exist.");
			throw new OCSBadRequestException("The workspace with {$id} id doesn't exist.");
		}

		$gid = UserGroup::get($id);

		$userGroup = $this->groupManager->get($gid);

		if (is_null($userGroup)) {
			$this->logger->error("The group with {$gid} group doesn't exist.");
			throw new OCSBadRequestException("The group with {$gid} group doesn't exist.");
		}

		$users = array_map(fn ($uid) => $this->userManager->get($uid), $uids);

		foreach ($users as $user) {
			$userGroup->addUser($user);
		}

		$spacename = $space->getSpaceName();

		$this->logger->info("Users are added in the {$spacename} workspace with the {$id} id.");

		return new DataResponse([
			'message' => "Users are added in the {$spacename} workspace with the {$id} id."
		], Http::STATUS_OK);

	}
}
