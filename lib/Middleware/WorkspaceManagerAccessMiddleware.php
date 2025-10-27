<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\WorkspaceManagerRequired;
use OCA\Workspace\Exceptions\Middleware\ForbiddenException;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class WorkspaceManagerAccessMiddleware extends Middleware {

	public function __construct(
		private IRequest $request,
		private UserService $userService,
		private SpaceService $spaceService,
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		$hasAttribute = $reflectionMethod->getAttributes(WorkspaceManagerRequired::class);

		if (empty($hasAttribute)) {
			return;
		}

		if ($this->userService->isUserGeneralAdmin()) {
			return;
		}

		if ($this->userService->isSpaceManager()) {
			$id = $this->request->getParam('id');

			$space = $this->spaceService->find($id);

			if (!$this->userService->isSpaceManagerOfSpace($space->jsonSerialize())) {
				throw new ForbiddenException("You are not Workspace Manager for the workspace with the id {$id}.");
			}

			return;
		}

		throw new ForbiddenException('You cannot access to this ressource');
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if (
			$controller instanceof OCSController
			&& $exception instanceof ForbiddenException) {
			return new JSONResponse([
				'message' => $exception->getMessage()
			], $exception->getCode());
		}

		throw $exception;
	}
}
