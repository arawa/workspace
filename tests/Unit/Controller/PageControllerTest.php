<?php

namespace OCA\Workspace\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCA\Workspace\Controller\PageController;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IUserManager;

class PageControllerTest extends TestCase {
	private $controller;
	private $userId = 'john';

	public function setUp(): void {
		$request = $this->getMockBuilder('OCP\IRequest')->getMock();

		$this->controller = new PageController(
			'workspace',
			$request,
			$this->userId,
			$this->createMock(IUserManager::class),
			$this->createMock(IGroupManager::class),
		);
	}

	public function testIndex(): void {
		$result = $this->controller->index();

		$this->assertEquals('index', $result->getTemplateName());
		$this->assertTrue($result instanceof TemplateResponse);
	}

}
