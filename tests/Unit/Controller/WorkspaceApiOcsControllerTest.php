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
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkspaceApiOcsControllerTest extends TestCase {

	private IRequest&MockObject $request;
	private GroupfolderHelper&MockObject $folderHelper;
	private IGroupManager&MockObject $groupManager;
	private LoggerInterface&MockObject $logger;
	private RootFolder&MockObject $rootFolder;
	private SpaceManager&MockObject $spaceManager;
	private UserService&MockObject $userService;
	private WorkspaceService&MockObject $workspaceService;
	private string $appName;
	private WorkspaceApiOcsController $controller;


	private const CURRENT_USER_IS_GENERAL_MANAGER = true;
	
	public function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->folderHelper = $this->createMock(GroupfolderHelper::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->rootFolder = $this->createMock(RootFolder::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->userService = $this->createMock(UserService::class);
		$this->workspaceService = $this->createMock(WorkspaceService::class);
		$this->appName = 'workspace';
		
		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->folderHelper,
			$this->groupManager,
			$this->logger,
			$this->rootFolder,
			$this->spaceManager,
			$this->userService,
			$this->workspaceService,
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

	public function testFindAllReturnsValidDataResponse(): void {
		$this->workspaceService
			->expects($this->once())
			->method('getAll')
			->willReturn([
				[
					'id' => 1,
					'groupfolder_id' => 1,
					'name' => 'Espace01',
					'color_code' => '#46221f'
				]
			])
		;

		$this->rootFolder
			->expects($this->any())
			->method('getRootFolderStorageId')
			->willReturn(2)
		;

		$this->folderHelper
			->expects($this->any())
			->method('getFolder')
			->willReturnOnConsecutiveCalls(
				[
					'id' => 1,
					'mount_point' => 'Espace01',
					'groups' => [
						'SPACE-GE-1' => 31,
						'SPACE-U-1' => 31
					],
					'quota' => -3,
					'size' => 0,
					'acl' => true,
					'manage' => [
						[
							'type' => 'group',
							'id' => 'SPACE-GE-1',
							'displayname' => 'WM-Espace01'
						]
					]
				]
			);

		$groupUser = $this->createMock(IGroup::class);
		$groupUser
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-U-1')
		;
		$groupUser
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('U-Espace01')
		;
		$groupUser
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupUser
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;

		$groupWorkspaceManager = $this->createMock(IGroup::class);
		$groupWorkspaceManager
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-GE-1')
		;
		$groupWorkspaceManager
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('WM-Espace01')
		;
		$groupWorkspaceManager
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupWorkspaceManager
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;

		$this->groupManager
			->expects($this->any())
			->method('get')
			->willReturn($groupUser, $groupWorkspaceManager)
		;

		$this->userService
			->expects($this->any())
			->method('isUserGeneralAdmin')
			->willReturn(self::CURRENT_USER_IS_GENERAL_MANAGER)
		;

		$groupFormatter = Mockery::mock(GroupFormatter::class);
		$groupFormatter
			->shouldReceive('formatGroups')
			->with([$groupUser, $groupWorkspaceManager])
			->andReturn([
				'SPACE-GE-1' => [
					'gid' => 'SPACE-GE-1',
					'displayName' => 'WM-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-GE-1'
				],
				'SPACE-U-1' => [
					'gid' => 'SPACE-U-1',
					'displayName' => 'U-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-U-1'
				]
			])
		;
		
		$actual = $this->controller->findAll();

		$expected = new DataResponse(
			[
				[
					'id' => 1,
					'mount_point' => 'Espace01',
					'groups' => [
						'SPACE-GE-1' => [
							'gid' => 'SPACE-GE-1',
							'displayName' => 'WM-Espace01',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-GE-1'
						],
						'SPACE-U-1' => [
							'gid' => 'SPACE-U-1',
							'displayName' => 'U-Espace01',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-U-1'
						]
					],
					'quota' => -3,
					'size' => 0,
					'acl' => true,
					'manage' => [
						[
							'type' => 'group',
							'id' => 'SPACE-GE-1',
							'displayname' => 'WM-Espace01'
						]
					],
					'groupfolder_id' => 1,
					'name' => 'Espace01',
					'color_code' => '#46221f',
					'users' => (object)[],
					'userCount' => 0,
					'added_groups' => (object)[]
				]
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
		$this->assertInstanceOf(Response::class, $actual);
		$this->assertInstanceOf(DataResponse::class, $actual, 'The response must be a DataResponse for OCS API');
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
