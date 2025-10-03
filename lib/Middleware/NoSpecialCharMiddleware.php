<?php

namespace OCA\Workspace\Middleware;

use OCA\Workspace\Attribute\NoSpecialChar;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\IRequest;

class NoSpecialCharMiddleware extends Middleware {
	public function __construct(
		private IRequest $request,
		private WorkspaceCheckService $workspaceCheckService,
	) {
	}

	public function beforeController($controller, $methodName): void {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		$hasAttribute = $reflectionMethod->getAttributes(NoSpecialChar::class);

		if (empty($hasAttribute)) {
			return;
		}

		$name = $this->request->getParam('name');
		$specialCharsReadable = implode(' ', str_split(WorkspaceCheckService::CHARACTERS_SPECIAL));

		if (is_null($name)) {
			throw new OCSBadRequestException('The name parameter is probably has special characters: ' . $specialCharsReadable);
		}

		$hasSpecialChars = $this->workspaceCheckService->containSpecialChar($name);
		if ($hasSpecialChars) {
			$message = "The name contains special characters which are not allowed: {$specialCharsReadable}"; // result: "The name parameter is probably has special characters: / : * ? \" < > | \\"
			throw new OCSBadRequestException($message);
		}
	}

	public function afterException($controller, $methodName, \Exception $exception) {
		return new JSONResponse(
			[
				'message' => $exception->getMessage()
			],
			Http::STATUS_BAD_REQUEST
		);
	}
}
