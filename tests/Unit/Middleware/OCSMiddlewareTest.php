<?php

namespace OCA\Workspace\Tests\Unit\Middleware;

use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Middleware\OCSMiddleware;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCSController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OCSMiddlewareTest extends TestCase {
	private MockObject&LoggerInterface $logger;
	private OCSMiddleware $middleware;

	public function setUp(): void {
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->middleware = new OCSMiddleware($this->logger);
	}

	public function testKeepsTheStatusOfAnHttpException(): void {
		$this->logger->expects($this->never())->method('error');

		$response = $this->middleware->afterException(
			$this->createMock(OCSController::class),
			'find',
			new NotFoundException('No workspace found with id 4')
		);

		$this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
		$this->assertSame(['message' => 'No workspace found with id 4'], $response->getData());
	}

	/**
	 * The dispatcher wraps a Throwable (here a TypeError) into an Exception
	 * with the same code, 0, which is not an HTTP status.
	 */
	public function testAnswers500AndLogsAnUnexpectedError(): void {
		$exception = new \Exception('Argument #1 ($folderId) must be of type int, null given', 0, new \TypeError());

		$this->logger
			->expects($this->once())
			->method('error')
			->with($exception->getMessage(), ['exception' => $exception]);

		$response = $this->middleware->afterException($this->createMock(OCSController::class), 'edit', $exception);

		$this->assertSame(Http::STATUS_INTERNAL_SERVER_ERROR, $response->getStatus());
		$this->assertSame(['message' => $exception->getMessage()], $response->getData());
	}

	public function testRethrowsForOtherControllers(): void {
		$exception = new \Exception('front error');

		$this->expectExceptionObject($exception);

		$this->middleware->afterException($this->createMock(Controller::class), 'index', $exception);
	}
}
