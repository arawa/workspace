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

namespace OCA\Workspace\Middleware;

use OCA\Workspace\Middleware\Attribute\SpaceAdminRequired;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

class IsSpaceAdminMiddleware extends Middleware {

	public function __construct(
		private IControllerMethodReflector $reflector,
		private IRequest $request,
		private UserService $userService,
		private SpaceService $spaceService
	) {
	}

	public function beforeController($controller, $methodName): void {
        $reflectionMethod = new \ReflectionMethod($controller, $methodName);
        $hasAttribute = !empty($reflectionMethod->getAttributes(SpaceAdminRequired::class));
		if ($hasAttribute) {
			$spaceId = $this->request->getParam('spaceId');
			$space = $this->spaceService->find($spaceId);
			if (!$this->userService->isSpaceManagerOfSpace($space->jsonSerialize()) && !$this->userService->isUserGeneralAdmin()) {
				throw new AccessDeniedException();
			}
		}
	}

	public function afterException($controller, $methodName, \Exception $exception): JSONResponse {
		if ($exception instanceof AccessDeniedException) {
			return new JSONResponse([
				'status' => 'forbidden',
				'msg' => 'You are not allowed to perform this action.'
			], Http::STATUS_FORBIDDEN);
		}

		return new JSONResponse([]);
	}
}
