<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\RequireExistingGroup;
use OCA\Workspace\Exceptions\Middleware\NotFoundException;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;
use OCP\IGroupManager;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class RequireExistingGroupMiddleware extends Middleware {

	public function __construct(
		private IRequest $request,
		private LoggerInterface $logger,
		private IGroupManager $groupManager,
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		$hasAttribute = $reflectionMethod->getAttributes(RequireExistingGroup::class);

		if (empty($hasAttribute)) {
			return;
		}

		$gid = $this->request->getParam('gid');

		$group = $this->groupManager->get($gid);

		if (is_null($group)) {
			throw new NotFoundException("Group with gid {$gid} was not found.");
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
