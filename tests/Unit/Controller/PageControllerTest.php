<?php

namespace OCA\Workspace\Tests\Unit\Controller;

use PHPUnit\Framework\TestCase;

use OCA\Workspace\Controller\PageController;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;

class PageControllerTest extends TestCase {
	private $controller;
	private $userId = 'john';

	public function setUp(): void {
		$this->controller = new PageController(
			$this->createMock(IUserManager::class),
			$this->createMock(UserService::class),
		);
	}

	public function testIndex(): void {
		$result = $this->controller->index();

		$this->assertEquals('index', $result->getTemplateName());
		$this->assertTrue($result instanceof TemplateResponse);
	}

}
