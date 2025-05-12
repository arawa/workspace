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
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
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

	public function tearDown(): void {
		Mockery::close();
	}

	public function testFindReturnsValidDataResponse(): void {
		$spaceId = 4;

		/** @var array space mocked */
		$space = [
			'id' => 4,
			'mount_point' => 'Espace04',
			'groups' => [
				'SPACE-GE-4' => [
					'gid' => 'SPACE-GE-4',
					'displayName' => 'WM-Espace04',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-GE-4'
				],
				'SPACE-U-4' => [
					'gid' => 'SPACE-U-4',
					'displayName' => 'U-Espace04',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-U-4'
				]
			],
			'quota' => -3,
			'size' => 0,
			'acl' => true,
			'manage' => [
				[
					'type' => 'group',
					'id' => 'SPACE-GE-4',
					'displayname' => 'WM-Espace04'
				]
			],
			'groupfolder_id' => 4,
			'name' => 'Espace04',
			'color_code' => '#93b250',
			'users' => (object)[],
			'userCount' => 0,
			'added_groups' => (object)[]
		];

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($spaceId)
			->willReturn($space)
		;

		$actual = $this->controller->find($spaceId);

		$expected = new DataResponse(
			[
				'id' => 4,
				'mount_point' => 'Espace04',
				'groups' => [
					'SPACE-GE-4' => [
						'gid' => 'SPACE-GE-4',
						'displayName' => 'WM-Espace04',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-GE-4'
					],
					'SPACE-U-4' => [
						'gid' => 'SPACE-U-4',
						'displayName' => 'U-Espace04',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-U-4'
					]
				],
				'quota' => -3,
				'size' => 0,
				'acl' => true,
				'manage' => [
					[
						'type' => 'group',
						'id' => 'SPACE-GE-4',
						'displayname' => 'WM-Espace04'
					]
				],
				'groupfolder_id' => 4,
				'name' => 'Espace04',
				'color_code' => '#93b250',
				'users' => (object)[],
				'userCount' => 0,
				'added_groups' => (object)[]
			],
			Http::STATUS_OK
		);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertInstanceOf(Response::class, $actual);
		$this->assertInstanceOf(DataResponse::class, $actual, 'The response must be a DataResponse for OCS API');
	}

	public function testDeleteWorkspace(): void {
		$id = 33;
		
		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($id)
			->willReturn([
				'id' => 33,
				'mount_point' => 'Space33',
				'groups' => [
					'SPACE-GE-33' => [
						'gid' => 'SPACE-GE-33',
						'displayName' => 'WM-Space33',
						'types' =>
							[
								'Database'
							],
						'usersCount' => 0,
						'slug' => 'SPACE-GE-33',
					],
					'SPACE-U-33' => [
						'gid' => 'SPACE-U-33',
						'displayName' => 'U-Space33',
						'types' =>
							[
								'Database'
							],
						'usersCount' => 0,
						'slug' => 'SPACE-U-33',
					],
				],
				'quota' => -3,
				'size' => 0,
				'acl' => true,
				'manage' => [
					'type' => 'group',
					'id' => 'SPACE-GE-33',
					'displayname' => 'WM-Space33',
				],
				'groupfolder_id' => 20,
				'name' => 'Space33',
				'color_code' => '#0b63ec',
				'userCount' => 0,
				'users' => [],
				'added_groups' => (object)[],
			])
		;

		$this->spaceManager
			->expects($this->once())
			->method('remove')
			->with($id)
		;

		$actual = $this->controller->delete($id);

		$expected = new DataResponse(
			[
				'name' => 'Space33',
				'groups' => [
					'SPACE-GE-33',
					'SPACE-U-33'
				],
				'space_id' => 33,
				'groupfolder_id' => 20,
				'state' => 'delete'
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

	public function testThrowsOCSNotFoundExceptionWhenGroupfolderNotFound(): void {
		$spaceId = 4;
		$folderId = 4;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($spaceId)
			->willThrowException(new NotFoundException("Failed loading groupfolder with the folderId {$folderId}"));
		;

		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage("Failed loading groupfolder with the folderId {$folderId}");

		$this->controller->find($spaceId);
	}

	public function testThrowsOCSExceptionWhenAnyExceptionThrown(): void {
		$spaceId = 4;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($spaceId)
			->willThrowException(new \Exception('Error'));
		;

		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('Error');

		$this->controller->find($spaceId);
	}
}
