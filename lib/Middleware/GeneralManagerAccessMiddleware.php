<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\GeneralManagerRequired;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\IRequest;

class GeneralManagerAccessMiddleware extends Middleware {
	public function __construct(
		private IRequest $request,
		private SpaceService $spaceService,
		private UserService $userService,
	) {
	}

	public function beforeController(Controller $controller, string $methodName): void {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		$hasAttribute = $reflectionMethod->getAttributes(GeneralManagerRequired::class);

		if (empty($hasAttribute)) {
			return;
		}
		
		if ($this->userService->isUserGeneralAdmin()) {
			return;
		}

		throw new OCSForbiddenException('You cannot access to this ressource');
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		return new JSONResponse([
			'message' => $exception->getMessage()
		], Http::STATUS_FORBIDDEN);
	}
}
