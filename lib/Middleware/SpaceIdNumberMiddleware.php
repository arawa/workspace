<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\SpaceIdNumber;
use OCA\Workspace\Exceptions\Middleware\BadRequestException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class SpaceIdNumberMiddleware extends Middleware {

	public function __construct(
		private IRequest $request,
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		$hasAttribute = $reflectionMethod->getAttributes(SpaceIdNumber::class);

		if (empty($hasAttribute)) {
			return;
		}

		$id = (int)$this->request->getParam('id');

		if (!is_int($id)) {
			throw new BadRequestException("The workspace id {$id} must be an integer.");
		}

		if ($id <= 0) {
			throw new BadRequestException("The workspace id {$id} must be superior or equal to 1.");
		}
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($controller instanceof OCSController
			&& $exception instanceof BadRequestException) {
			return new JSONResponse([
				'message' => $exception->getMessage()
			], $exception->getCode());
		}

		throw $exception;
	}
}
