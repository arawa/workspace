<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2025 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

use Mockery;
use OCA\Workspace\Controller\WorkspaceApiOcsController;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkspaceApiOcsControllerTest extends TestCase {

	private IRequest&MockObject $request;
	private LoggerInterface&MockObject $logger;
	private SpaceManager&MockObject $spaceManager;
	private SpaceMapper&MockObject $spaceMapper;
	private string $appName;
	private WorkspaceApiOcsController $controller;
	
	public function setUp(): void {
		$this->appName = 'workspace';
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->spaceMapper = $this->createMock(SpaceMapper::class);
		
		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->logger,
			$this->spaceManager,
			$this->spaceMapper,
			$this->appName
		);
	}

	public function tearDown(): void {
		Mockery::close();
	}

	public function testAddUsersToWorkspaceReturnsValidResponse(): void {
		$spaceId = 1;
		$spacename = 'Espace01';
		$uids = ['user1', 'user2'];

		$this->spaceManager
			->expects($this->once())
			->method('addUsersInWorkspace')
			->with($spaceId, $uids)
		;

		/** @var Space&MockObject */
		$space = $this->createMock(Space::class);

		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($spaceId)
			->willReturn($space)
		;

		$space
			->expects($this->once())
			->method('getSpaceName')
			->willReturn($spacename)
		;
		
		$actual = $this->controller->addUsersInWorkspace($spaceId, $uids);

		$expected = new DataResponse(
			[
				'message' => 'Users are added in the Espace01 workspace with the 1 id.'
			],
			Http::STATUS_OK
		)
		;

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
	}
}
