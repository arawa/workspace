<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\WorkspaceMemberRequired;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IRequest;

class WorkspaceMemberAccessMiddleware extends Middleware {

	public function __construct(
		private IRequest $request,
		private UserService $userService,
		private SpaceService $spaceService,
	) {
	}

	public function beforeController(Controller $controller, string $methodName): void {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		$hasAttribute = $reflectionMethod->getAttributes(WorkspaceMemberRequired::class);

		if (empty($hasAttribute)) {
			return;
		}

		if ($this->userService->isUserGeneralAdmin()) {
			return;
		}

		$spaceId = $this->request->getParam('spaceId');
		$space = $spaceId !== null ? $this->spaceService->find((int)$spaceId) : null;

		if ($space === null) {
			throw new AccessDeniedException();
		}

		$space = $space->jsonSerialize();

		if ($this->userService->isSpaceManagerOfSpace($space) || $this->userService->isUserOfSpace($space)) {
			return;
		}

		throw new AccessDeniedException();
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($exception instanceof AccessDeniedException) {
			return new JSONResponse([
				'status' => 'forbidden',
				'msg' => 'You are not allowed to perform this action.'
			], Http::STATUS_FORBIDDEN);
		}

		throw $exception;
	}
}
