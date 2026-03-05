<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Workspace\Tests\Unit\Controller;

use OCA\Workspace\Controller\PageController;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PageControllerTest extends TestCase {
	private $controller;
	private $userId = 'john';
	private MockObject&UserService $userService;
	private MockObject&IConfig $config;
	private MockObject&IInitialState $initialState;
	private MockObject&SpaceManager $spaceManager;
	private MockObject&IGroupManager $groupManager;
	private MockObject&IUserSession $session;

	public function setUp(): void {
		$this->userService = $this->createMock(UserService::class);
		$this->config = $this->createMock(IConfig::class);
		$this->session = $this->createMock(IUserSession::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);

		$this->controller = new PageController(
			$this->userService,
			$this->config,
			$this->initialState,
			$this->session,
			$this->spaceManager,
			$this->groupManager
		);
	}

	public function testIndex(): void {
		$gid = 'GeneralManager';
		$user = $this->createMock(IUser::class);

		$this->session
			->expects($this->exactly(2))
			->method('getUser')
			->willReturn($user)
		;

		$user
			->expects($this->any())
			->method('getUID')
			->willReturn($this->userId)
		;

		$generalManagerGroupMocked = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($generalManagerGroupMocked)
		;

		$generalManagerGroupMocked
			->expects($this->once())
			->method('inGroup')
			->with($user)
			->willReturn(true)
		;

		$this->spaceManager
			->expects($this->once())
			->method('countWorkspaces')
			->willReturn(5)
		;

		$result = $this->controller->index();

		$this->assertEquals('index', $result->getTemplateName());
		$this->assertTrue($result instanceof TemplateResponse);
	}
}
