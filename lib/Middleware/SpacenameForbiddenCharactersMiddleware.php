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
		var_dump('test');
		die;
		if ($exception instanceof WorkspaceNameSpecialCharException) {
			$specialCharsReadable = implode(' ', str_split(WorkspaceCheckService::CHARACTERS_SPECIAL));

			$data = $controller instanceof OCSController
				? [ 'message' => "Your Workspace name must not contain the following characters: {$specialCharsReadable}" ]
				: [
					'title' => 'Error creating workspace',
					'statuscode' => $exception->getCode(),
					'message' => 'Your Workspace name must not contain the following characters: {specialChars}',
					'args_message' => [ 'specialChars' => $specialCharsReadable ]
				]
			;

			return new JSONResponse($data, $exception->getCode());
		}

		throw $exception;
	}
}
