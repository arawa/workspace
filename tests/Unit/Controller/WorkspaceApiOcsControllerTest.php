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

class WorkspaceApiOcsControllerTest extends TestCase {

	private IRequest&MockObject $request;
	private IGroupManager&MockObject $groupManager;
	private IUserManager&MockObject $userManager;
	private SpaceManager&MockObject $spaceManager;
	private string $appName;
	private WorkspaceApiOcsController $controller;

	public function setUp(): void {
		$this->appName = 'workspace';
		$this->request = $this->createMock(IRequest::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);

		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->groupManager,
			$this->userManager,
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
