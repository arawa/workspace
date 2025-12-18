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
use OCA\Workspace\Exceptions\GroupException;
use OCA\Workspace\Exceptions\InvalidParamException;
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Service\Group\GroupsWorkspace;
use OCA\Workspace\Service\Group\GroupsWorkspaceService;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Validator\WorkspaceEditParamsValidator;
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

	private GroupsWorkspaceService&MockObject $groupsWorkspaceService;
	private IGroupManager&MockObject $groupManager;
	private IRequest&MockObject $request;
	private IUserManager&MockObject $userManager;
	private LoggerInterface&MockObject $logger;
	private SpaceManager&MockObject $spaceManager;
	private SpaceMapper&MockObject $spaceMapper;
	private string $appName;
	private UserService&MockObject $userService;
	private WorkspaceApiOcsController $controller;
	private WorkspaceEditParamsValidator&MockObject $editValidator;


	private const CURRENT_USER_IS_GENERAL_MANAGER = true;

	public function setUp(): void {
		$this->appName = 'workspace';
		$this->editValidator = $this->createMock(WorkspaceEditParamsValidator::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->groupsWorkspaceService = $this->createMock(GroupsWorkspaceService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->spaceMapper = $this->createMock(SpaceMapper::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userService = $this->createMock(UserService::class);

		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->logger,
			$this->groupManager,
			$this->userManager,
			$this->spaceManager,
			$this->editValidator,
			$this->groupsWorkspaceService,
			$this->spaceMapper,
			$this->userService,
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
			'usersCount' => 0,
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
				'usersCount' => 0,
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
		$this->assertInstanceOf(DataResponse::class, $actual, 'Response must be a DataResponse for OCS API');
	}
	public function testRemoveUserFromGroup(): void {
		$id = 1;
		$gid = 'SPACE-U-1';
		$uids = [
			'user1',
			'user2',
			'user3',
			'user4',
			'user5',
		];
		$space = [
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
			'color_code' => '#5ca609',
			'usersCount' => 0,
			'users' => [],
			'added_groups' => []
		];

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($group)
		;

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user4 = $this->createMock(IUser::class);
		$user5 = $this->createMock(IUser::class);

		$this->userManager
			->expects($this->any())
			->method('get')
			->with(
				$this->logicalOr(
					$this->equalTo($uids[0]),
					$this->equalTo($uids[1]),
					$this->equalTo($uids[2]),
					$this->equalTo($uids[3]),
					$this->equalTo($uids[4]),
				)
			)
			->willReturnOnConsecutiveCalls(
				$user1,
				$user2,
				$user3,
				$user4,
				$user5,
			)
		;

		$groupsWorkspace = Mockery::mock(GroupsWorkspace::class);

		$groupsWorkspace
			->shouldReceive('isWorkspaceUserGroupId')
			->with($gid)
			->andReturn(true)
		;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($id)
			->willReturn($space)
		;

		$this->spaceManager
			->expects($this->once())
			->method('removeUsersFromUserGroup')
		;

		$expected = new DataResponse([], Http::STATUS_NO_CONTENT);

		$actual = $this->controller->removeUsersFromGroup($id, $gid, $uids);

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getStatus(), $actual->getStatus());
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
			->expects($this->any())
			->method('getGID')
			->willReturn($gid)
		;

		$expected = new DataResponse([ 'gid' => 'SPACE-G-HR-1' ], Http::STATUS_CREATED);
		$actual = $this->controller->createSubGroup($id, $groupname);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertInstanceOf(Response::class, $actual, 'Response is not extended from Response class');
		$this->assertInstanceOf(DataResponse::class, $actual, 'Response is not extended from Response class');
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
				'usersCount' => 0
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
				'usersCount' => 0
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

	public function testFindAllAsGeneralManager(): void {
		$spaces
			= [
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
					'usersCount' => 0,
					'added_groups' => (object)[]
				]
			]
		;
		$name = null;

		$this->spaceManager
			->expects($this->once())
			->method('findAll')
			->willReturn($spaces)
		;

		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(self::CURRENT_USER_IS_GENERAL_MANAGER)
		;

		$actual = $this->controller->findAll($name);

		$expected = new DataResponse(
			$spaces,
			Http::STATUS_OK
		);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
	}

	public function testFindAllAsGeneralManagerWithSearchParameter(): void {
		$spaces
			= [
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
					'usersCount' => 0,
					'added_groups' => (object)[]
				],
				[
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
					'quota' => -3,
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
					'color_code' => '#46221f',
					'users' => (object)[],
					'usersCount' => 0,
					'added_groups' => (object)[]
				],
				[
					'id' => 3,
					'mount_point' => 'Human Ressource',
					'groups' => [
						'SPACE-GE-3' => [
							'gid' => 'SPACE-GE-3',
							'displayName' => 'WM-Human Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-GE-3'
						],
						'SPACE-U-3' => [
							'gid' => 'SPACE-U-3',
							'displayName' => 'U-Human Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-U-3'
						]
					],
					'quota' => -3,
					'size' => 0,
					'acl' => true,
					'manage' => [
						[
							'type' => 'group',
							'id' => 'SPACE-GE-3',
							'displayname' => 'WM-Human Ressource'
						]
					],
					'groupfolder_id' => 3,
					'name' => 'Human Ressource',
					'color_code' => '#46221f',
					'users' => (object)[],
					'usersCount' => 0,
					'added_groups' => (object)[]
				],
				[
					'id' => 4,
					'mount_point' => 'Tech Ressource',
					'groups' => [
						'SPACE-GE-4' => [
							'gid' => 'SPACE-GE-4',
							'displayName' => 'WM-Tech Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-GE-4'
						],
						'SPACE-U-4' => [
							'gid' => 'SPACE-U-4',
							'displayName' => 'U-Tech Ressource',
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
							'displayname' => 'WM-Tech Ressource'
						]
					],
					'groupfolder_id' => 1,
					'name' => 'Tech Ressource',
					'color_code' => '#46221f',
					'users' => (object)[],
					'usersCount' => 0,
					'added_groups' => (object)[]
				]
			]
		;

		$name = 'reSsoUrcE';

		$spacesSearched = [
			[
				'id' => 3,
				'mount_point' => 'Human Ressource',
				'groups' => [
					'SPACE-GE-3' => [
						'gid' => 'SPACE-GE-3',
						'displayName' => 'WM-Human Ressource',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-GE-3'
					],
					'SPACE-U-3' => [
						'gid' => 'SPACE-U-3',
						'displayName' => 'U-Human Ressource',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-U-3'
					]
				],
				'quota' => -3,
				'size' => 0,
				'acl' => true,
				'manage' => [
					[
						'type' => 'group',
						'id' => 'SPACE-GE-3',
						'displayname' => 'WM-Human Ressource'
					]
				],
				'groupfolder_id' => 3,
				'name' => 'Human Ressource',
				'color_code' => '#46221f',
				'users' => (object)[],
				'usersCount' => 0,
				'added_groups' => (object)[]
			],
			[
				'id' => 4,
				'mount_point' => 'Tech Ressource',
				'groups' => [
					'SPACE-GE-4' => [
						'gid' => 'SPACE-GE-4',
						'displayName' => 'WM-Tech Ressource',
						'types' => [
							'Database'
						],
						'usersCount' => 0,
						'slug' => 'SPACE-GE-4'
					],
					'SPACE-U-4' => [
						'gid' => 'SPACE-U-4',
						'displayName' => 'U-Tech Ressource',
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
						'displayname' => 'WM-Tech Ressource'
					]
				],
				'groupfolder_id' => 1,
				'name' => 'Tech Ressource',
				'color_code' => '#46221f',
				'users' => (object)[],
				'usersCount' => 0,
				'added_groups' => (object)[]
			]
		];

		$this->spaceManager
			->expects($this->once())
			->method('findAll')
			->willReturn($spaces)
		;

		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(self::CURRENT_USER_IS_GENERAL_MANAGER)
		;

		$actual = $this->controller->findAll($name);

		$expected = new DataResponse(
			$spacesSearched,
			Http::STATUS_OK
		);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
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
						'types'
							=> [
								'Database'
							],
						'usersCount' => 0,
						'slug' => 'SPACE-GE-33',
					],
					'SPACE-U-33' => [
						'gid' => 'SPACE-U-33',
						'displayName' => 'U-Space33',
						'types'
							=> [
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
				'usersCount' => 0,
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

		$expected = new DataResponse([], Http::STATUS_NO_CONTENT);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_NO_CONTENT, $actual->getStatus());
	}

	public function testFindAllAsWorkspaceManager(): void {
		$spaces
			= [
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
					'usersCount' => 0,
					'added_groups' => (object)[]
				],
				[
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
					'quota' => -3,
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
					'color_code' => '#46221f',
					'users' => (object)[],
					'usersCount' => 0,
					'added_groups' => (object)[]
				],
				[
					'id' => 3,
					'mount_point' => 'Human Ressource',
					'groups' => [
						'SPACE-GE-3' => [
							'gid' => 'SPACE-GE-3',
							'displayName' => 'WM-Human Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-GE-3'
						],
						'SPACE-U-3' => [
							'gid' => 'SPACE-U-3',
							'displayName' => 'U-Human Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-U-3'
						]
					],
					'quota' => -3,
					'size' => 0,
					'acl' => true,
					'manage' => [
						[
							'type' => 'group',
							'id' => 'SPACE-GE-3',
							'displayname' => 'WM-Human Ressource'
						]
					],
					'groupfolder_id' => 3,
					'name' => 'Human Ressource',
					'color_code' => '#46221f',
					'users' => (object)[],
					'usersCount' => 0,
					'added_groups' => (object)[]
				],
				[
					'id' => 4,
					'mount_point' => 'Tech Ressource',
					'groups' => [
						'SPACE-GE-4' => [
							'gid' => 'SPACE-GE-4',
							'displayName' => 'WM-Tech Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-GE-4'
						],
						'SPACE-U-4' => [
							'gid' => 'SPACE-U-4',
							'displayName' => 'U-Tech Ressource',
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
							'displayname' => 'WM-Tech Ressource'
						]
					],
					'groupfolder_id' => 1,
					'name' => 'Tech Ressource',
					'color_code' => '#46221f',
					'users' => (object)[],
					'usersCount' => 0,
					'added_groups' => (object)[]
				]
			]
		;
		$name = null;

		$this->spaceManager
			->expects($this->once())
			->method('findAll')
			->willReturn($spaces)
		;

		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(!self::CURRENT_USER_IS_GENERAL_MANAGER)
		;

		$this->userService
			->expects($this->exactly(4))
			->method('isSpaceManagerOfSpace')
			->willReturn(true, false, true, false)
		;

		$actual = $this->controller->findAll($name);

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
					'usersCount' => 0,
					'added_groups' => (object)[]
				],
				[
					'id' => 3,
					'mount_point' => 'Human Ressource',
					'groups' => [
						'SPACE-GE-3' => [
							'gid' => 'SPACE-GE-3',
							'displayName' => 'WM-Human Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-GE-3'
						],
						'SPACE-U-3' => [
							'gid' => 'SPACE-U-3',
							'displayName' => 'U-Human Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-U-3'
						]
					],
					'quota' => -3,
					'size' => 0,
					'acl' => true,
					'manage' => [
						[
							'type' => 'group',
							'id' => 'SPACE-GE-3',
							'displayname' => 'WM-Human Ressource'
						]
					],
					'groupfolder_id' => 3,
					'name' => 'Human Ressource',
					'color_code' => '#46221f',
					'users' => (object)[],
					'usersCount' => 0,
					'added_groups' => (object)[]
				],
			],
			Http::STATUS_OK
		);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
	}

	public function testFindAllAsWorkspaceManagerWithSearchParameter(): void {
		$spaces
			= [
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
				],
				[
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
					'quota' => -3,
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
					'color_code' => '#46221f',
					'users' => (object)[],
					'userCount' => 0,
					'added_groups' => (object)[]
				],
				[
					'id' => 3,
					'mount_point' => 'Human Ressource',
					'groups' => [
						'SPACE-GE-3' => [
							'gid' => 'SPACE-GE-3',
							'displayName' => 'WM-Human Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-GE-3'
						],
						'SPACE-U-3' => [
							'gid' => 'SPACE-U-3',
							'displayName' => 'U-Human Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-U-3'
						]
					],
					'quota' => -3,
					'size' => 0,
					'acl' => true,
					'manage' => [
						[
							'type' => 'group',
							'id' => 'SPACE-GE-3',
							'displayname' => 'WM-Human Ressource'
						]
					],
					'groupfolder_id' => 3,
					'name' => 'Human Ressource',
					'color_code' => '#46221f',
					'users' => (object)[],
					'userCount' => 0,
					'added_groups' => (object)[]
				],
				[
					'id' => 4,
					'mount_point' => 'Tech Ressource',
					'groups' => [
						'SPACE-GE-4' => [
							'gid' => 'SPACE-GE-4',
							'displayName' => 'WM-Tech Ressource',
							'types' => [
								'Database'
							],
							'usersCount' => 0,
							'slug' => 'SPACE-GE-4'
						],
						'SPACE-U-4' => [
							'gid' => 'SPACE-U-4',
							'displayName' => 'U-Tech Ressource',
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
							'displayname' => 'WM-Tech Ressource'
						]
					],
					'groupfolder_id' => 1,
					'name' => 'Tech Ressource',
					'color_code' => '#46221f',
					'users' => (object)[],
					'userCount' => 0,
					'added_groups' => (object)[]
				]
			]
		;

		$name = 'space';

		$spacesSearched = [
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
			],
		]
		;

		$this->spaceManager
			->expects($this->once())
			->method('findAll')
			->willReturn($spaces)
		;

		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(!self::CURRENT_USER_IS_GENERAL_MANAGER)
		;

		$this->userService
			->expects($this->exactly(4))
			->method('isSpaceManagerOfSpace')
			->willReturn(true, false, true, false)
		;

		$actual = $this->controller->findAll($name);

		$expected = new DataResponse($spacesSearched, Http::STATUS_OK);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertInstanceOf(Response::class, $actual);
		$this->assertInstanceOf(DataResponse::class, $actual, 'Response must be a DataResponse for OCS API');
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

		$this->assertInstanceOf(Response::class, $actual, 'Response is not extended from Response class');
		$this->assertInstanceOf(DataResponse::class, $actual, 'Response is not extended from Response class');
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
			->willThrowException(new NotFoundException("Failed loading groupfolder with folderId {$folderId}"));
		;

		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage("Failed loading groupfolder with folderId {$folderId}");

		$this->controller->find($spaceId);
	}
	public function testAddUsersToWorkspaceReturnsValidResponse(): void {
		$spaceId = 1;
		$spacename = 'Espace01';
		$uids = ['user1', 'user2'];
		$count = count($uids);

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
				'message' => "{$count} users were added in the Espace01 workspace with id 1."
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
			'name' => 'Espace02 Modified',
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
			'usersCount' => 0,
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
		$actual = $this->controller->edit($id, $params['name'], $params['color'], $params['quota']);

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
			->willThrowException(new InvalidParamException('Name key must be a string'))
		;

		$this->expectException(InvalidParamException::class);
		$this->expectExceptionMessage('Name key must be a string');

		$this->controller->edit($id, $params['name']);
	}

	public function testEditWithQuotaParamInString(): void {
		$id = 1;
		$params = [
			'quota' => '42',
		];

		$this->editValidator
			->expects($this->once())
			->method('validate')
			->willThrowException(new InvalidParamException('Quota key must be a integer'))
		;

		$this->expectException(InvalidParamException::class);
		$this->expectExceptionMessage('Quota key must be a integer');

		$this->controller->edit($id, null, null, $params['quota']);
	}

	public function testEditWithColorParamInInteger(): void {
		$id = 1;
		$params = [
			'color' => 42,
		];

		$this->editValidator
			->expects($this->once())
			->method('validate')
			->willThrowException(new InvalidParamException('Color key must be a string'))
		;

		$this->expectException(InvalidParamException::class);
		$this->expectExceptionMessage('Color key must be a string');

		$this->controller->edit($id, null, $params['color']);
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
		$this->expectExceptionMessage("User with uid {$uid} doesn't exists on your Nextcloud instance.");

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

		$expected = new DataResponse([], Http::STATUS_NO_CONTENT);

		/** @var DataResponse */
		$actual = $this->controller->removeUserAsWorkspaceManager($id, $uid);

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals($expected->getStatus(), Http::STATUS_NO_CONTENT);
	}

	public function testRemoveUserFromGroupWithUsersAreNull(): void {
		$id = 1;
		$gid = 'SPACE-U-1';
		$uids = [
			'user1',
			'user2',
			'user3',
			'user4',
			'user5',
		];

		$space = [
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
			'color_code' => '#5ca609',
			'usersCount' => 0,
			'users' => [],
			'added_groups' => []
		];

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($id)
			->willReturn($space)
		;

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($group)
		;

		$user1 = $this->createMock(IUser::class);
		$user2 = null;
		$user3 = $this->createMock(IUser::class);
		$user4 = null;
		$user5 = $this->createMock(IUser::class);

		$this->userManager
			->expects($this->any())
			->method('get')
			->with(
				$this->logicalOr(
					$this->equalTo($uids[0]),
					$this->equalTo($uids[1]),
					$this->equalTo($uids[2]),
					$this->equalTo($uids[3]),
					$this->equalTo($uids[4]),
				)
			)
			->willReturnOnConsecutiveCalls(
				$user1,
				$user2,
				$user3,
				$user4,
				$user5,
			)
		;

		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage("These users does not exist on your Nextcloud instance:\n- user2\n- user4");

		$this->controller->removeUsersFromGroup($id, $gid, $uids);
	}


	public function testAddUsersInSubgroup(): void {
		$id = 1;
		$gid = "SPACE-G-Talk-{$id}";
		$displayname = "G-Talk-{$id}";
		$uids = [
			'user1',
			'user2',
			'user3',
			'user4'
		];
		$workspace = [
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
					'usersCount' => 8,
					'slug' => 'SPACE-U-1'
				],
				'SPACE-G-Talk-1' => [
					'gid' => 'SPACE-G-Talk-1',
					'displayName' => 'G-Talk-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-Talk-1'
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
			'color_code' => '#ac1a8e',
			'userCount' => 8,
			'users' => [],
			'added_groups' => []
		];
		$spacename = 'Espace01';

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($group)
		;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($id)
			->willReturn($workspace)
		;

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user4 = $this->createMock(IUser::class);

		$users = [
			$user1,
			$user2,
			$user3,
			$user4,
		];

		$this->userManager
			->expects($this->exactly(4))
			->method('get')
			->with(
				$this->logicalOr(
					$this->equalTo($uids[0]),
					$this->equalTo($uids[1]),
					$this->equalTo($uids[2]),
					$this->equalTo($uids[3]),
				)
			)
			->willReturnOnConsecutiveCalls(
				$user1,
				$user2,
				$user3,
				$user4,
			)
		;

		$groupsWorkspace = Mockery::mock(GroupsWorkspace::class);

		$groupsWorkspace
			->shouldReceive('isWorkspaceSubGroup')
			->with($gid)
			->andReturn(true)
		;

		$groupsWorkspace
			->shouldReceive('isWorkspaceGroup')
			->with($group)
			->andReturn(true)
		;

		$this->spaceManager
			->expects($this->once())
			->method('addUsersToSubGroup')
			->with($workspace, $group, $users)
		;

		$group
			->expects($this->once())
			->method('getDisplayName')
			->willReturn($displayname)
		;

		$count = count($uids);

		$actual = $this->controller->addUsersToGroup($id, $gid, $uids);

		$expected = new DataResponse(
			[
				'message' => "{$count} users were added in the {$displayname} ({$gid}) group from the {$spacename} workspace ({$id})."
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


	public function testAddUsersInUserGroup(): void {
		$id = 1;
		$gid = "SPACE-U-{$id}";
		$displayname = "U-{$id}";
		$uids = [
			'user1',
			'user2',
			'user3',
			'user4'
		];
		$workspace = [
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
					'usersCount' => 8,
					'slug' => 'SPACE-U-1'
				],
				'SPACE-G-Talk-1' => [
					'gid' => 'SPACE-G-Talk-1',
					'displayName' => 'G-Talk-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-Talk-1'
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
			'color_code' => '#ac1a8e',
			'userCount' => 8,
			'users' => [],
			'added_groups' => []
		];
		$spacename = 'Espace01';

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($group)
		;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($id)
			->willReturn($workspace)
		;

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user4 = $this->createMock(IUser::class);

		$users = [
			$user1,
			$user2,
			$user3,
			$user4,
		];

		$this->userManager
			->expects($this->exactly(4))
			->method('get')
			->with(
				$this->logicalOr(
					$this->equalTo($uids[0]),
					$this->equalTo($uids[1]),
					$this->equalTo($uids[2]),
					$this->equalTo($uids[3]),
				)
			)
			->willReturnOnConsecutiveCalls(
				$user1,
				$user2,
				$user3,
				$user4,
			)
		;

		$groupsWorkspace = Mockery::mock(GroupsWorkspace::class);

		$groupsWorkspace
			->shouldReceive('isWorkspaceUserGroupId')
			->with($gid)
			->andReturn(true)
		;

		$this->spaceManager
			->expects($this->once())
			->method('addUsersToGroup')
			->with($group, $users)
		;

		$group
			->expects($this->once())
			->method('getDisplayName')
			->willReturn($displayname)
		;

		$count = count($uids);

		$actual = $this->controller->addUsersToGroup($id, $gid, $uids);

		$expected = new DataResponse(
			[
				'message' => "{$count} users were added in the {$displayname} ({$gid}) group from the {$spacename} workspace ({$id})."
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


	public function testAddUsersInWorkspaceManagerGroup(): void {
		$id = 1;
		$gid = "SPACE-GE-{$id}";
		$displayname = "WM-{$id}";
		$uids = [
			'user1',
			'user2',
			'user3',
			'user4'
		];
		$workspace = [
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
					'usersCount' => 8,
					'slug' => 'SPACE-U-1'
				],
				'SPACE-G-Talk-1' => [
					'gid' => 'SPACE-G-Talk-1',
					'displayName' => 'G-Talk-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-Talk-1'
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
			'color_code' => '#ac1a8e',
			'userCount' => 8,
			'users' => [],
			'added_groups' => []
		];
		$spacename = 'Espace01';

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($group)
		;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($id)
			->willReturn($workspace)
		;

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user4 = $this->createMock(IUser::class);

		$users = [
			$user1,
			$user2,
			$user3,
			$user4,
		];

		$this->userManager
			->expects($this->exactly(4))
			->method('get')
			->with(
				$this->logicalOr(
					$this->equalTo($uids[0]),
					$this->equalTo($uids[1]),
					$this->equalTo($uids[2]),
					$this->equalTo($uids[3]),
				)
			)
			->willReturnOnConsecutiveCalls(
				$user1,
				$user2,
				$user3,
				$user4,
			)
		;

		$groupsWorkspace = Mockery::mock(GroupsWorkspace::class);

		$groupsWorkspace
			->shouldReceive('isWorkspaceAdminGroupId')
			->with($gid)
			->andReturn(true)
		;

		$this->spaceManager
			->expects($this->once())
			->method('addUsersToWorkspaceManagerGroup')
			->with($workspace, $group, $users)
		;

		$group
			->expects($this->once())
			->method('getDisplayName')
			->willReturn($displayname)
		;

		$count = count($uids);

		$actual = $this->controller->addUsersToGroup($id, $gid, $uids);

		$expected = new DataResponse(
			[
				'message' => "{$count} users were added in the {$displayname} ({$gid}) group from the {$spacename} workspace ({$id})."
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

	public function testErrorInUidsWhenAddingUsersToAGroup(): void {
		$id = 1;
		$gid = "SPACE-GE-{$id}";
		$displayname = "WM-{$id}";
		$uids = [
			'user1',
			'user2',
			'user3',
			'user42'
		];
		$workspace = [
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
					'usersCount' => 8,
					'slug' => 'SPACE-U-1'
				],
				'SPACE-G-Talk-1' => [
					'gid' => 'SPACE-G-Talk-1',
					'displayName' => 'G-Talk-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-Talk-1'
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
			'color_code' => '#ac1a8e',
			'userCount' => 8,
			'users' => [],
			'added_groups' => []
		];
		$spacename = 'Espace01';

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($group)
		;

		$this->spaceManager
			->expects($this->once())
			->method('get')
			->with($id)
			->willReturn($workspace)
		;

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user42 = null;

		$this->userManager
			->expects($this->exactly(4))
			->method('get')
			->with(
				$this->logicalOr(
					$this->equalTo($uids[0]),
					$this->equalTo($uids[1]),
					$this->equalTo($uids[2]),
					$this->equalTo($uids[3]),
				)
			)
			->willReturnOnConsecutiveCalls(
				$user1,
				$user2,
				$user3,
				$user42,
			)
		;

		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage("These users doesn't exist on your Nextcloud instance:\n- user42");

		$this->controller->addUsersToGroup($id, $gid, $uids);
	}

	public function testFindUsersById(): void {
		$id = 1;
		$usersFormatted = [
			'robotunit0-1' => [
				'uid' => 'robotunit0-1',
				'name' => 'robotunit0-1',
				'email' => 'robotunit0-1@planetexpress.com',
				'subtitle' => 'robotunit0-1@planetexpress.com',
				'groups' => [
					'SPACE-U-1'
				],
				'is_connected' => false,
				'profile' => 'http =>//stable30.local/index.php/u/robotunit0-1',
				'role' => 'user'
			],
			'robotunit0-2' => [
				'uid' => 'robotunit0-2',
				'name' => 'robotunit0-2',
				'email' => 'robotunit0-2@planetexpress.com',
				'subtitle' => 'robotunit0-2@planetexpress.com',
				'groups' => [
					'SPACE-U-1'
				],
				'is_connected' => false,
				'profile' => 'http =>//stable30.local/index.php/u/robotunit0-2',
				'role' => 'user'
			],
			'robotunit0-3' => [
				'uid' => 'robotunit0-3',
				'name' => 'robotunit0-3',
				'email' => 'robotunit0-3@planetexpress.com',
				'subtitle' => 'robotunit0-3@planetexpress.com',
				'groups' => [
					'SPACE-U-1'
				],
				'is_connected' => false,
				'profile' => 'http =>//stable30.local/index.php/u/robotunit0-3',
				'role' => 'user'
			],
			'robotunit0-4' => [
				'uid' => 'robotunit0-4',
				'name' => 'robotunit0-4',
				'email' => 'robotunit0-4@planetexpress.com',
				'subtitle' => 'robotunit0-4@planetexpress.com',
				'groups' => [
					'SPACE-U-1'
				],
				'is_connected' => false,
				'profile' => 'http =>//stable30.local/index.php/u/robotunit0-4',
				'role' => 'user'
			],
			'robotunit0-5' => [
				'uid' => 'robotunit0-5',
				'name' => 'robotunit0-5',
				'email' => 'robotunit0-5@planetexpress.com',
				'subtitle' => 'robotunit0-5@planetexpress.com',
				'groups' => [
					'SPACE-U-1'
				],
				'is_connected' => false,
				'profile' => 'http =>//stable30.local/index.php/u/robotunit0-5',
				'role' => 'user'
			],
			'robotunit0-6' => [
				'uid' => 'robotunit0-6',
				'name' => 'robotunit0-6',
				'email' => 'robotunit0-6@planetexpress.com',
				'subtitle' => 'robotunit0-6@planetexpress.com',
				'groups' => [
					'SPACE-U-1'
				],
				'is_connected' => false,
				'profile' => 'http =>//stable30.local/index.php/u/robotunit0-6',
				'role' => 'user'
			],
			'user1' => [
				'uid' => 'user1',
				'name' => 'user1',
				'email' => null,
				'subtitle' => null,
				'groups' => [
					'SPACE-U-1'
				],
				'is_connected' => false,
				'profile' => 'http =>//stable30.local/index.php/u/user1',
				'role' => 'user'
			],
			'user2' => [
				'uid' => 'user2',
				'name' => 'user2',
				'email' => null,
				'subtitle' => null,
				'groups' => [
					'SPACE-U-1'
				],
				'is_connected' => false,
				'profile' => 'http =>//stable30.local/index.php/u/user2',
				'role' => 'user'
			]
		];

		$this->spaceManager
			->expects($this->once())
			->method('findUsersById')
			->with($id)
			->willReturn($usersFormatted)
		;

		$actual = $this->controller->findUsersById($id);

		$expected = new DataResponse($usersFormatted, Http::STATUS_OK);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertInstanceOf(DataResponse::class, $actual);
	}

	public function testRemoveGroup(): void {
		$id = 1;
		$gid = 'SPACE-G-HR-1';

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($group)
		;

		$this->groupsWorkspaceService
			->expects($this->once())
			->method('removeGroup')
			->with($group)
		;

		$actual = $this->controller->removeGroup($id, $gid);

		$expected = new DataResponse([], Http::STATUS_NO_CONTENT);

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_NO_CONTENT, $actual->getStatus());
		$this->assertInstanceOf(DataResponse::class, $actual);
	}

	public function testRemoveUserGroupThrowsException(): void {
		$id = 1;
		$gid = 'SPACE-U-1';

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($group)
		;

		$this->groupsWorkspaceService
			->expects($this->once())
			->method('removeGroup')
			->with($group)
			->willThrowException(new GroupException('You cannot remove the user group (U-) or the workspace manager group (WM-) as they are essential for the app\'s operation.'))
		;

		$this->expectException(OCSException::class);
		$this->expectExceptionMessage('You cannot remove the user group (U-) or the workspace manager group (WM-) as they are essential for the app\'s operation.');

		$this->controller->removeGroup($id, $gid);
	}
}
