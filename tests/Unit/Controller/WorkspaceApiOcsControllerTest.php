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

use OCA\Workspace\Controller\WorkspaceApiOcsController;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkspaceApiOcsControllerTest extends TestCase {

	private IRequest&MockObject $request;
	private LoggerInterface&MockObject $logger;
	private SpaceManager&MockObject $spaceManager;
	private string $appName;
	private WorkspaceApiOcsController $controller;
	
	public function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->appName = 'workspace';
		
		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->logger,
			$this->spaceManager,
			$this->appName
		);
	}

	public function testCreateReturnsValidDataResponse(): void {
		$spacename = "Space01";
		
		$this->spaceManager
			->expects($this->once())
			->method('create')
			->with($spacename)
			->willReturn([
				"name" => "Space01",
				"id" => 1,
				"id_space" => 1,
				"folder_id" => 1,
				"color" => "#413160",
				"groups" => [
				  "SPACE-GE-1" => [
					"gid" => "SPACE-GE-1",
					"displayName" => "WM-Space01",
					"types" => [
					  "Database"
					],
					"usersCount" => 0,
					"slug" => "SPACE-GE-1"
				  ],
				  "SPACE-U-1" => [
					"gid" => "SPACE-U-1",
					"displayName" => "U-Space01",
					"types" => [
					  "Database"
					],
					"usersCount" => 0,
					"slug" => "SPACE-U-1"
				  ]
				],
				"added_groups" => [],
				"quota" => -3,
				"size" => 0,
				"acl" => true,
				"manage" => [
				  [
					"type" => "group",
					"id" => "SPACE-GE-1",
					"displayname" => "WM-Space01"
				  ]
				],
				"userCount" => 0
			  ]
			)
		;

		$actual = $this->controller->create($spacename);

		$expected = new DataResponse(
			[
				"name" => "Space01",
				"id" => 1,
				"id_space" => 1,
				"folder_id" => 1,
				"color" => "#413160",
				"groups" => [
				  "SPACE-GE-1" => [
					"gid" => "SPACE-GE-1",
					"displayName" => "WM-Space01",
					"types" => [
					  "Database"
					],
					"usersCount" => 0,
					"slug" => "SPACE-GE-1"
				  ],
				  "SPACE-U-1" => [
					"gid" => "SPACE-U-1",
					"displayName" => "U-Space01",
					"types" => [
					  "Database"
					],
					"usersCount" => 0,
					"slug" => "SPACE-U-1"
				  ]
				],
				"added_groups" => [],
				"quota" => -3,
				"size" => 0,
				"acl" => true,
				"manage" => [
				  [
					"type" => "group",
					"id" => "SPACE-GE-1",
					"displayname" => "WM-Space01"
				  ]
				],
				"userCount" => 0
			  ],
			Http::STATUS_CREATED
		)
		;

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_CREATED, $actual->getStatus());
	}
}
