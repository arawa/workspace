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
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private SpaceManager $spaceManager,
		private LoggerInterface $logger,
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	#[SpaceIdNumber]
	#[RequireExistingSpace]
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'DELETE',
		url: '/api/v1/space/{id}/users',
		requirements: ['id' => '\d+']
	)]
	public function removeUsersInWorkspace(int $id, array $uids): Response {
		$this->spaceManager->removeUsersFromWorkspace($id, $uids);
		$uidsStringify = implode(', ', $uids);
		$this->logger->info("These users are removed from the workspace with the id {$id} : {$uidsStringify}");

		return new DataResponse([], Http::STATUS_NO_CONTENT);
	}
}
