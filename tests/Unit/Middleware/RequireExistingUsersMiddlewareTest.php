<?php

namespace OCA\Workspace\Tests\Unit\Middleware;

use OCA\Workspace\Controller\WorkspaceApiOcsController;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Middleware\RequireExistingUsersMiddleware;
use OCA\Workspace\Service\Group\GroupsWorkspaceService;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Validator\WorkspaceEditParamsValidator;
use OCA\Workspace\Space\SpaceManager;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

use function PHPUnit\Framework\exactly;

class RequireExistingUsersMiddlewareTest extends TestCase {

	private MockObject&IRequest $request;
	private MockObject&IUserManager $userManager;

	private WorkspaceApiOcsController $workspaceApiController;
	private RequireExistingUsersMiddleware $middleware;

	public function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->workspaceApiController = new WorkspaceApiOcsController(
			$this->createMock(IRequest::class),
			$this->createMock(LoggerInterface::class),
			$this->createMock(IGroupManager::class),
			$this->createMock(IUserManager::class),
			$this->createMock(SpaceManager::class),
			$this->createMock(WorkspaceEditParamsValidator::class),
			$this->createMock(GroupsWorkspaceService::class),
			$this->createMock(IUserSession::class),
			$this->createMock(SpaceMapper::class),
			$this->createMock(UserService::class),
			'workspace',
		);

		$this->middleware = new RequireExistingUsersMiddleware(
			$this->request,
			$this->userManager
		)
		;
	}

	public function testBeforeControllerWithUsersValid(): void {
		$uids = [
			'bob',
			'jane',
			'alice'
		];
		$methodName = 'addUsersToGroup';


		/** @var ObjectMock&IUser */
		$bob = $this->createMock(IUser::class);
		/** @var ObjectMock&IUser */
		$jane = $this->createMock(IUser::class);
		/** @var ObjectMock&IUser */
		$alice = $this->createMock(IUser::class);

		$this->request
			->expects($this->once())
			->method('getParam')
			->with('uids')
			->willReturn($uids)
		;

		$this->userManager
			->expects(exactly(3))
			->method('get')
			->willReturnOnConsecutiveCalls($bob, $jane, $alice)
		;

		$this->middleware->beforeController(
			$this->workspaceApiController,
			$methodName
		);
	}
}
