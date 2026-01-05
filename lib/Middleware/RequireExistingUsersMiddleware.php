<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\RequireExistingUsers;
use OCA\Workspace\Exceptions\Middleware\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use OCP\IUserManager;

class RequireExistingUsersMiddleware extends Middleware {

	public function __construct(
		private IRequest $request,
		private IUserManager $userManager,
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		$hasAttribute = $reflectionMethod->getAttributes(RequireExistingUsers::class);

		if (empty($hasAttribute)) {
			return;
		}

		$uids = $this->request->getParam('uids');


		if ($uids === null) {
			return;
		}

		$usersNotFound = [];
		foreach ($uids as $uid) {
			$user = $this->userManager->get($uid);
			if (is_null($user)) {
				$usersNotFound[] = $uid;
				continue;
			}

			$users[] = $user;
		}

		if (!empty($usersNotFound)) {
			$usersNotFound = array_map(fn ($uid) => "- {$uid}", $usersNotFound);
			$usersNotFound = implode("\n", $usersNotFound);
			throw new NotFoundException("These users were not found on your Nextcloud instance:\n{$usersNotFound}");
		}
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		if ($controller instanceof OCSController
			&& $exception instanceof NotFoundException) {
			return new JSONResponse([
				'message' => $exception->getMessage()
			], $exception->getCode());
		}

		throw $exception;
	}
}
