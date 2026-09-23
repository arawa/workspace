<?php

namespace OCA\Workspace\Tests\Unit\Middleware;

use OCA\Workspace\Controller\WorkspaceController;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCA\Workspace\Middleware\WorkspaceMemberAccessMiddleware;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkspaceMemberAccessMiddlewareTest extends TestCase {

	private MockObject&IRequest $request;
	private MockObject&UserService $userService;
	private MockObject&SpaceService $spaceService;
	private MockObject&WorkspaceController $controller;
	private WorkspaceMemberAccessMiddleware $middleware;

	public function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userService = $this->createMock(UserService::class);
		$this->spaceService = $this->createMock(SpaceService::class);

		// No method is mocked, so the attributes of the real methods stay readable by reflection.
		$this->controller = $this->getMockBuilder(WorkspaceController::class)
			->disableOriginalConstructor()
			->onlyMethods([])
			->getMock()
		;

		$this->middleware = new WorkspaceMemberAccessMiddleware(
			$this->request,
			$this->userService,
			$this->spaceService,
		);
	}

	private function mockSpace(int $spaceId): void {
		/** @var MockObject&Space */
		$space = $this->createMock(Space::class);
		$space
			->method('jsonSerialize')
			->willReturn(['id' => $spaceId])
		;

		$this->request
			->method('getParam')
			->with('spaceId')
			->willReturn((string)$spaceId)
		;

		$this->spaceService
			->method('find')
			->with($spaceId)
			->willReturn($space)
		;
	}

	public function testMethodWithoutAttributeIsNotChecked(): void {
		$this->userService
			->expects($this->never())
			->method('isUserGeneralAdmin')
		;

		$this->request
			->expects($this->never())
			->method('getParam')
		;

		$this->middleware->beforeController($this->controller, 'findAll');
	}

	public function testGeneralManagerIsAllowed(): void {
		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(true)
		;

		$this->spaceService
			->expects($this->never())
			->method('find')
		;

		$this->userService
			->expects($this->never())
			->method('isSpaceManagerOfSpace')
		;

		$this->middleware->beforeController($this->controller, 'getUsers');
	}

	public function testWorkspaceManagerOfTheSpaceIsAllowed(): void {
		$this->mockSpace(6);

		$this->userService
			->expects($this->once())
			->method('isSpaceManagerOfSpace')
			->with(['id' => 6])
			->willReturn(true)
		;

		// A manager is accepted without checking the user group.
		$this->userService
			->expects($this->never())
			->method('isUserOfSpace')
		;

		$this->middleware->beforeController($this->controller, 'getAdmins');
	}

	public function testMemberOfTheSpaceIsAllowed(): void {
		$this->mockSpace(6);

		$this->userService
			->expects($this->once())
			->method('isSpaceManagerOfSpace')
			->with(['id' => 6])
			->willReturn(false)
		;

		$this->userService
			->expects($this->once())
			->method('isUserOfSpace')
			->with(['id' => 6])
			->willReturn(true)
		;

		$this->middleware->beforeController($this->controller, 'getUsers');
	}

	public function testUserOutsideTheSpaceIsDenied(): void {
		$this->mockSpace(4);

		$this->userService->method('isSpaceManagerOfSpace')->willReturn(false);
		$this->userService->method('isUserOfSpace')->willReturn(false);

		$this->expectException(AccessDeniedException::class);

		$this->middleware->beforeController($this->controller, 'getUsers');
	}

	public function testUnknownSpaceIsDenied(): void {
		$this->request
			->method('getParam')
			->with('spaceId')
			->willReturn('999')
		;

		$this->spaceService
			->method('find')
			->with(999)
			->willReturn(null)
		;

		$this->expectException(AccessDeniedException::class);

		$this->middleware->beforeController($this->controller, 'getAdmins');
	}

	public function testAccessDeniedIsTurnedIntoForbiddenResponse(): void {
		$response = $this->middleware->afterException($this->controller, 'getUsers', new AccessDeniedException());

		$this->assertSame(Http::STATUS_FORBIDDEN, $response->getStatus());
	}
}
