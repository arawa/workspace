<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Exceptions\SpacenameExistException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;
use OCP\IL10N;

class DuplicateSpacenameMiddleware extends Middleware {

	public function __construct(
		private IL10N $l10n,
	) {
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($exception instanceof SpacenameExistException) {

			$data = $controller instanceof OCSController
				? [ 'message' => $this->l10n->t("This space or groupfolder already exists. Please, use another space name.\nIf a \"toto\" space exists, you cannot create the \"tOTo\" space.\nPlease check also the groupfolder doesn't exist.") ]
				: [
					'title' => $this->l10n->t('Error - Duplicate space name'),
					'statuscode' => $exception->getCode(),
					'message' => $this->l10n->t("This space or groupfolder already exists. Please, use another space name.\nIf a \"toto\" space exists, you cannot create the \"tOTo\" space.\nPlease check also the groupfolder doesn't exist."),
					'args_message' => []
				]
			;

			return new JSONResponse($data, $exception->getCode());
		}

		throw $exception;
	}
}
