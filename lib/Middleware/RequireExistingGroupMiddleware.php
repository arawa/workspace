<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\RequireExistingGroup;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCS\OCSNotFoundException;
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
			throw new OCSNotFoundException("The group with the gid {$gid} is not found.");
		}
	}

	public function afterException(Controller $controller, string $methodName, Exception $exception): Response {
		return new JSONResponse([
			'message' => $exception->getMessage()
		], $exception->getCode());
	}
}
