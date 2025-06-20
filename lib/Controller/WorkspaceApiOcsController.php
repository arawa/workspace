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

use OCA\Workspace\Attribute\WorkspaceManagerRequired;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class WorkspaceApiOcsController extends OCSController {
	public function __construct(
		IRequest $request,
		private SpaceManager $spaceManager,
		public $appName,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * @return Response<{
	 * 	id: int,
	 * 	mount_point: string,
	 * 	groups: array,
	 * 	quota: int,
	 * 	size: int,
	 * 	acl: bool,
	 *  manage: array<Object>
	 * 	groupfolder_id: int,
	 * 	name: string,
	 * 	color_code: string,
	 *  userCount: int,
	 *  users: array<Object>
	 *  added_groups: array<Object>
	 * }, Http::STATUS_OK>
	 *
	 * 200: Workspace returned
	 */
	#[WorkspaceManagerRequired]
	#[NoAdminRequired]
	#[FrontpageRoute(
		verb: 'GET',
		url: '/api/v1/space/{id}',
		requirements: ['id' => '\d+']
	)]
	public function find(int $id): Response {
		$space = $this->spaceManager->get($id);
		return new DataResponse($space, Http::STATUS_OK);
	}
}
