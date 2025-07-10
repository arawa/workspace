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
use OCA\Workspace\Exceptions\InvalidParamException;
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Service\Validator\WorkspaceEditParamsValidator;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
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
	private IGroupManager&MockObject $groupManager;
	private IUserManager&MockObject $userManager;
	private SpaceManager&MockObject $spaceManager;
	private WorkspaceEditParamsValidator&MockObject $editValidator;
	private string $appName;
	private WorkspaceApiOcsController $controller;
	
	public function setUp(): void {
		$this->appName = 'workspace';
		$this->editValidator = $this->createMock(WorkspaceEditParamsValidator::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		
		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->logger,
			$this->groupManager,
			$this->userManager,
			$this->spaceManager,
			$this->editValidator,
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

	public function testCreateReturnsValidDataResponse(): void {
		$spacename = 'Space01';
		
		$this->spaceManager
			->expects($this->once())
			->method('create')
			->with($spacename)
			->willReturn([
				'name' => 'Space01',
				'id' => 1,
				'id_space' => 1,
				'folder_id' => 1,
				'color' => '#413160',
				'groups' => [
					'SPACE-GE-1' => [
						'gid' => 'SPACE-GE-1',
						'displayName' => 'WM-Space01',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-GE-1'
					],
					'SPACE-U-1' => [
						'gid' => 'SPACE-U-1',
						'displayName' => 'U-Space01',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-U-1'
					]
				],
				'added_groups' => [],
				'quota' => -3,
				'size' => 0,
				'acl' => true,
				'manage' => [
					[
						'type' => 'group',
						'id' => 'SPACE-GE-1',
						'displayname' => 'WM-Space01'
					]
				],
				'userCount' => 0
			]
			)
		;

		$actual = $this->controller->create($spacename);

		$expected = new DataResponse(
			[
				'name' => 'Space01',
				'id' => 1,
				'id_space' => 1,
				'folder_id' => 1,
				'color' => '#413160',
				'groups' => [
					'SPACE-GE-1' => [
						'gid' => 'SPACE-GE-1',
						'displayName' => 'WM-Space01',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-GE-1'
					],
					'SPACE-U-1' => [
						'gid' => 'SPACE-U-1',
						'displayName' => 'U-Space01',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-U-1'
					]
				],
				'added_groups' => [],
				'quota' => -3,
				'size' => 0,
				'acl' => true,
				'manage' => [
					[
						'type' => 'group',
						'id' => 'SPACE-GE-1',
						'displayname' => 'WM-Space01'
					]
				],
				'userCount' => 0
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
				'id' => 33,
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

public function testFindGroupsBySpaceIdReturnsValidDataResponse(): void {
		$spaceId = 1;

		$this->spaceManager
			->expects($this->once())
			->method('findGroupsBySpaceId')
			->with($spaceId)
			->willReturn([
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

		$actual = $this->controller->findGroupsBySpaceId($spaceId);

		$expected = new DataResponse(
			[
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
			Http::STATUS_OK
		)
		;
 
		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertInstanceOf(Response::class, $actual, 'The response is not extended of Response class');
		$this->assertInstanceOf(DataResponse::class, $actual, 'The response is not extended of Response class');
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
	public function testRemoveUsersToWorkspaceReturnsValidResponse(): void {
		$spaceId = 1;
		$uids = ['user1', 'user2'];

		$actual = $this->controller->removeUsersInWorkspace($spaceId, $uids);

		$expected = new DataResponse(
			[
			],
			Http::STATUS_NO_CONTENT
		)
		;

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_NO_CONTENT, $actual->getStatus());
	}

	public function testThrowsOCSExceptionWhenAnyExceptionThrown(): void {
		$spaceId = 4;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($spaceId)
			->willThrowException(new InvalidParamException('Error'));
		;

		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('Error');

		$this->controller->find($spaceId);
	}

	public function testEdit(): void {
		$id = 1;
		$params = [
			'name' => 'Espace02',
			'quota' => 214748364800, // 200Gb
			'color' => '#ACB5AC',
		];

		$space = [
			'id' => 2,
			'mount_point' => 'Espace02',
			'groups' => [
				'SPACE-GE-2' => [
					'gid' => 'SPACE-GE-2',
					'displayName' => 'WM-Espace02',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-GE-2'
				],
				'SPACE-U-2' => [
					'gid' => 'SPACE-U-2',
					'displayName' => 'U-Espace02',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-U-2'
				]
			],
			'quota' => 214748364800,
			'size' => 0,
			'acl' => true,
			'manage' => [
				[
					'type' => 'group',
					'id' => 'SPACE-GE-2',
					'displayname' => 'WM-Espace02'
				]
			],
			'groupfolder_id' => 2,
			'name' => 'Espace02',
			'color_code' => '#ACB5AC',
			'userCount' => 0,
			'users' => [],
			'added_groups' => []
		];

		$this->editValidator
			->expects($this->once())
			->method('validate')
		;

		$this->spaceManager
			->expects($this->once())
			->method('setColor')
			->willReturn($this->createMock(Space::class))
		;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($id)
			->willReturn($space)
		;

		$this->spaceManager
			->expects($this->once())
			->method('renameGroups')
		;

		$this->spaceManager
			->expects($this->once())
			->method('rename')
		;

		$this->spaceManager
			->expects($this->once())
			->method('setQuota')
		;

		$expected = new DataResponse($params, Http::STATUS_OK);

		/** @var DataResponse */
		$actual = $this->controller->edit($id, $params);

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
	}

	public function testShouldAddUserAsWorkspaceManager(): void {
		$uid = 'user1';
		$id = 1;
		
		$user = $this->createMock(IUser::class);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user)
		;

		$this->spaceManager
			->expects($this->once())
			->method('addUserAsWorkspaceManager')
			->with($id, $uid)
		;

		$expected = new DataResponse([ 'uid' => $uid ], Http::STATUS_OK);

		$actual = $this->controller->addUserAsWorkspaceManager($id, $uid);

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getStatus(), $actual->getStatus());
	}

	public function testEditWithNameParamInInteger(): void {
		$id = 1;
		$params = [
			'name' => 42,
		];

		$this->editValidator
			->expects($this->once())
			->method('validate')
			->willThrowException(new InvalidParamException('The name key must be a string'))
		;

		$this->expectException(InvalidParamException::class);
		$this->expectExceptionMessage('The name key must be a string');

		$this->controller->edit($id, $params);
	}

	public function testEditWithQuotaParamInString(): void {
		$id = 1;
		$params = [
			'quota' => '42',
		];

		$this->editValidator
			->expects($this->once())
			->method('validate')
			->willThrowException(new InvalidParamException('The quota key must be a integer'))
		;

		$this->expectException(InvalidParamException::class);
		$this->expectExceptionMessage('The quota key must be a integer');

		$this->controller->edit($id, $params);
	}

	public function testEditWithColorParamInInteger(): void {
		$id = 1;
		$params = [
			'color' => 42,
		];

		$this->editValidator
			->expects($this->once())
			->method('validate')
			->willThrowException(new InvalidParamException('The color key must be a string'))
		;

		$this->expectException(InvalidParamException::class);
		$this->expectExceptionMessage('The color key must be a string');

		$this->controller->edit($id, $params);
	}

	public function testShouldThrowOcsNotFoundWhenAddingUserAsWorkspaceManager(): void {
		$uid = 'user1';
		$id = 1;

		$user = null;

		$this->userManager
			->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user)
		;

		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage("The user with the uid {$uid} doesn't exist in your Nextcloud instance.");

		$this->controller->addUserAsWorkspaceManager($id, $uid);
	}

	public function testRemoveUserAsWorkspaceManager(): void {
		$id = 1;
		$uid = 'user1';

		$user = $this->createMock(IUser::class);
		/** @var IGroup&MockObject */
		$managerGroup = $this->createMock(IGroup::class);

		$managerGroupGid = "SPACE-GE-{$id}";

		$this->userManager
			->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user)
		;

		$workspaceManagerGroupMock = Mockery::mock(WorkspaceManagerGroup::class);
		$workspaceManagerGroupMock
			->shouldReceive('get')
			->with($id)
			->andReturn($managerGroupGid)
		;

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($managerGroupGid)
			->willReturn($managerGroup)
		;

		$expected = new DataResponse([], Http::STATUS_OK);

		/** @var DataResponse */
		$actual = $this->controller->removeUserAsWorkspaceManager($id, $uid);

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals($expected->getStatus(), Http::STATUS_OK);
	}
}
