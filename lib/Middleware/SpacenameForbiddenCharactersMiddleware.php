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
use OCP\IL10N;

class SpacenameForbiddenCharactersMiddleware extends Middleware {

	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($exception instanceof WorkspaceNameSpecialCharException) {
			$specialCharsReadable = implode(' ', str_split(WorkspaceCheckService::CHARACTERS_SPECIAL));

			$data = $controller instanceof OCSController
				? [ 'message' => $this->l10n->t('Your Workspace name must not contain the following characters: %s', [ $specialCharsReadable ]) ]
				: [
					'title' => $this->l10n->t('Error creating workspace'),
					'statuscode' => $exception->getCode(),
					'message' => $this->l10n->t('Your Workspace name must not contain the following characters: %s', [ $specialCharsReadable ])	,
					'args_message' => [ 'specialChars' => $specialCharsReadable ]
				]
			;

			return new JSONResponse($data, $exception->getCode());
		}

		throw $exception;
	}
}
