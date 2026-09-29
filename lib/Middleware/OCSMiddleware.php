<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;
use Psr\Log\LoggerInterface;

/**
 * Default Middleware for OCS Controllers which catch exceptions not handled by other middleware.
 */
class OCSMiddleware extends Middleware {
	public function __construct(
		private LoggerInterface $logger,
	) {
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($controller instanceof OCSController) {
			$status = (int)$exception->getCode();

			// An unexpected error (e.g. a TypeError, wrapped by the dispatcher)
			// carries no HTTP status: answer 500 and log it, as nothing else will.
			if ($status < 400 || $status >= 600) {
				$this->logger->error($exception->getMessage(), ['exception' => $exception]);
				$status = Http::STATUS_INTERNAL_SERVER_ERROR;
			}

			return new JSONResponse([
				'message' => $exception->getMessage(),
			], $status);
		}

		throw $exception;
	}
}
