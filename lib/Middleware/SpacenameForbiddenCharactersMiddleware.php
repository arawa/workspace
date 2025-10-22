<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Exceptions\WorkspaceNameSpecialCharException;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;

class SpacenameForbiddenCharactersMiddleware extends Middleware {

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		$specialCharsReadable = implode(' ', str_split(WorkspaceCheckService::CHARACTERS_SPECIAL));

		if ($controller instanceof OCSController && $exception instanceof WorkspaceNameSpecialCharException) {
			return new JSONResponse([
				'message' => "Your Workspace name must not contain the following characters: {$specialCharsReadable}"
			], $exception->getCode());
		}

		if ($exception instanceof WorkspaceNameSpecialCharException) {
			return new JSONResponse([
				'title' => 'Error creating workspace',
				'statuscode' => $exception->getCode(),
				'message' => 'Your Workspace name must not contain the following characters: {specialChars}',
				'args_message' => [ 'specialChars' => $specialCharsReadable ]
			], $exception->getCode());
		}

		throw $exception;
	}
}
