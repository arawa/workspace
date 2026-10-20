<?php

namespace OCA\Workspace\Tests\Unit\Service\Hub;

use OCA\Workspace\Service\Hub\AppIconService;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AppIconServiceTest extends TestCase {
	private MockObject&IAppManager $appManager;
	private AppIconService $service;
	private string $appPath;

	public function setUp(): void {
		$this->appManager = $this->createMock(IAppManager::class);
		$this->service = new AppIconService($this->appManager);

		$this->appPath = sys_get_temp_dir() . '/workspace-app-icon-' . uniqid();
		mkdir($this->appPath . '/img', 0777, true);
	}

	public function tearDown(): void {
		array_map('unlink', glob($this->appPath . '/img/*'));
		rmdir($this->appPath . '/img');
		rmdir($this->appPath);
	}

	public function testReadsTheIconsFromTheAppFolder(): void {
		file_put_contents($this->appPath . '/img/app.svg', '<svg>light</svg>');
		file_put_contents($this->appPath . '/img/app-dark.svg', '<svg>dark</svg>');
		$this->appManager->method('getAppPath')->with('groupfolders')->willReturn($this->appPath);

		$this->assertSame(
			['icon_path' => '<svg>light</svg>', 'dark_icon_path' => '<svg>dark</svg>'],
			$this->service->getIcons('groupfolders')
		);
	}

	public function testPrefersTheIconNamedAfterTheApp(): void {
		file_put_contents($this->appPath . '/img/groupfolders.svg', '<svg>named</svg>');
		file_put_contents($this->appPath . '/img/app.svg', '<svg>generic</svg>');
		$this->appManager->method('getAppPath')->willReturn($this->appPath);

		$this->assertSame(
			['icon_path' => '<svg>named</svg>', 'dark_icon_path' => null],
			$this->service->getIcons('groupfolders')
		);
	}

	/**
	 * Any other app is refused before its folder is even looked up.
	 */
	public function testRefusesAnAppThatIsNotAllowed(): void {
		$this->appManager->expects($this->never())->method('getAppPath');

		$this->assertNull($this->service->getIcons('files'));
		$this->assertNull($this->service->getIcons('../../config'));
	}

	public function testReturnsNullWhenTheAppIsNotInstalled(): void {
		$this->appManager->method('getAppPath')->willThrowException(new AppPathNotFoundException());

		$this->assertNull($this->service->getIcons('groupfolders'));
	}
}
