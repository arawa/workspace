<?php

namespace OCA\Workspace\Tests\Unit\Controller;

use OCA\Workspace\Controller\WorkspaceApiOcsController;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IGroup;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkspaceApiOcsControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private SpaceManager&MockObject $spaceManager;
	private string $appName;
	private WorkspaceApiOcsController $controller;

	public function setUp(): void {
		$this->appName = 'workspace';
		$this->request = $this->createMock(IRequest::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		
		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->appName,
			$this->spaceManager,
		);
	}

	public function testCreateSubGroup() {
		$id = 1;
		$groupname = 'HR';

		$gid = 'SPACE-G-HR-1';

		$group = $this->createMock(IGroup::class);
		
		$this->spaceManager
			->expects($this->once())
			->method('createSubGroup')
			->with($id, $groupname)
			->willReturn($group)
		;

		$group
			->expects($this->once())
			->method('getGID')
			->willReturn($gid)
		;
				
		$expected = new DataResponse([ 'gid' => 'SPACE-G-HR-1' ], Http::STATUS_CREATED);
		$actual = $this->controller->createSubGroup($id, $groupname);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertInstanceOf(Response::class, $actual, 'The response is not extended of Response class');
		$this->assertInstanceOf(DataResponse::class, $actual, 'The response is not extended of Response class');
		$this->assertEquals(Http::STATUS_CREATED, $actual->getStatus());
		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
	}
}
