<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Exceptions\SpacenameExistException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;

class DuplicateSpacenameMiddleware extends Middleware {

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($controller instanceof OCSController && $exception instanceof SpacenameExistException) {
			return new JSONResponse([
				'message' => "This space or groupfolder already exists. Please, use another space name.\nIf a \"toto\" space exists, you cannot create the \"tOTo\" space.\nPlease check also the groupfolder doesn't exist."
			], $exception->getCode());
		}

		if ($exception instanceof SpacenameExistException) {
			return new JSONResponse([
				'title' => 'Error - Duplicate space name',
				'statuscode' => $exception->getCode(),
				'message' => "This space or groupfolder already exists. Please, use another space name.\nIf a \"toto\" space exists, you cannot create the \"tOTo\" space.\nPlease check also the groupfolder doesn't exist.",
				'args_message' => []
			], $exception->getCode());
		}

		throw $exception;
	}
}
