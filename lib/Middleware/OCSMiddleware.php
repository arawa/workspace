<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;

/**
 * Default Middleware for OCS Controllers which catch exceptions not handled by other middleware.
 */
class OCSMiddleware extends Middleware {
	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($controller instanceof OCSController) {
			return new JSONResponse([
				'message' => $exception->getMessage(),
			], $exception->getCode());
		}

		throw $exception;
	}
}
