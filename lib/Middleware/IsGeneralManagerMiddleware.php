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

use Exception;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IRequest;

class IsGeneralManagerMiddleware extends Middleware {

    public function __construct(
		private IControllerMethodReflector $reflector,
		private IRequest $request,
		private UserService $userService
	) {
	}

	public function beforeController($controller, $methodName): void {
		if ($this->reflector->hasAnnotation('GeneralManagerRequired')) {
			if (!$this->userService->isUserGeneralAdmin()) {
				throw new AccessDeniedException();
			}
		}

		return;
	}

	public function afterException($controller, $methodName, Exception $exception): void {
		if ($exception instanceof AccessDeniedException) {
			return new JSONResponse([
				'status' => 'forbidden',
				'msg' => 'You are not allowed to perform this action'
			], Http::STATUS_FORBIDDEN);
		}
	}
}
