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
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkspaceApiOcsControllerTest extends TestCase {

	private IRequest&MockObject $request;
	private SpaceManager&MockObject $spaceManager;
	private UserService&MockObject $userService;
	private string $appName;
	private WorkspaceApiOcsController $controller;


	private const CURRENT_USER_IS_GENERAL_MANAGER = true;
	
	public function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->userService = $this->createMock(UserService::class);
		$this->appName = 'workspace';
		
		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->spaceManager,
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

	public function testFindAllAsGeneralManager(): void {
		$spaces = 
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
			]
		;
		
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('name')
			->willReturn(null)
		;
		
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
		
		$actual = $this->controller->findAll();

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
		$spaces = 
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
		];

		$this->request
			->expects($this->once())
			->method('getParam')
			->with('name')
			->willReturn('reSsoUrcE')
		;

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

		$actual = $this->controller->findAll();

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

	public function testFindAllAsWorkspaceManager(): void {
		$spaces = 
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
		
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('name')
			->willReturn(null)
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
		$spaces = 
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
		
		$this->request
			->expects($this->once())
			->method('getParam')
			->with('name')
			->willReturn('space')
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
		
		$actual = $this->controller->findAll();

		$expected = new DataResponse($spacesSearched, Http::STATUS_OK);

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
