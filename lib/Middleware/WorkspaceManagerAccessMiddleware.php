<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\WorkspaceManagerRequired;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSForbiddenException;
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
				throw new OCSForbiddenException("You are not Workspace Manager for the workspace with the id {$id}.");
			}
			
			return;
		}

		throw new OCSForbiddenException('You cannot access to this ressource');
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		return new JSONResponse([
			'message' => $exception->getMessage()
		], $exception->getCode());
	}
}
