<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
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

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\SpaceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SpaceController extends Controller {

	public function __construct(
		IRequest $request,
		private $AppName,
		private SpaceMapper $spaceMapper,
		private SpaceService $spaceService
	) {
		parent::__construct($AppName, $request);
	}

	/**
	 * @NoAdminRequired
	 */
	public function find(int $id): DataResponse {
		return new DataResponse($this->spaceService->find($id));
	}

	/**
	 * @NoAdminRequired
	 */
	public function findAll(): DataResponse {
		return new DataResponse($this->spaceService->findAll());
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 */
	public function updateColorCode(string $colorCode, int $spaceId): DataResponse {
		return new DataResponse($this->spaceService->updateColorCode($colorCode, (int)$spaceId));
	}
}
