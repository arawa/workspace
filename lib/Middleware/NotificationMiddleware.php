<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Exceptions\AbstractNotification;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;

class NotificationMiddleware extends Middleware {
	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($exception instanceof AbstractNotification) {
			return new JSONResponse([
				'title' => $exception->getTitle(),
				'statuscode' => $exception->getCode(),
				'message' => $exception->getMessage(),
				'args_message' => $exception->getArgsMessage()
			], $exception->getCode());
		}

		throw $exception;
	}
}
