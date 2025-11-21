<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCA\Workspace\Attribute\RequireExistingSpace;
use OCA\Workspace\Exceptions\Middleware\NotFoundException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\SpaceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\OCSController;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class RequireExistingSpaceMiddleware extends Middleware {

	public function __construct(
		private GroupfolderHelper $folderHelper,
		private IRequest $request,
		private LoggerInterface $logger,
		private RootFolder $rootFolder,
		private SpaceService $spaceService,
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		$reflectionMethod = new \ReflectionMethod($controller, $methodName);
		$hasAttribute = $reflectionMethod->getAttributes(RequireExistingSpace::class);

		if (empty($hasAttribute)) {
			return;
		}

		$id = (int)$this->request->getParam('id');

		$space = $this->spaceService->find($id);

		if (is_null($space)) {
			throw new NotFoundException("Workspace with id {$id} was not found.");
		}

		$groupfolder = $this->folderHelper->getFolder($space->getGroupfolderId(), $this->rootFolder->getRootFolderStorageId());

		if ($groupfolder === false) {
			$this->logger->error('Failed loading groupfolder ' . $space->getGroupfolderId());
			throw new NotFoundException('Failed loading groupfolder ' . $space->getGroupfolderId());
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
