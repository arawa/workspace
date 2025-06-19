<?php

namespace OCA\Workspace\Tests\Unit\Controller;

use Mockery;
use OCA\Workspace\Controller\WorkspaceApiOcsController;
use OCA\Workspace\Service\Group\GroupsWorkspace;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkspaceApiOcsControllerTest extends TestCase {

	private IGroupManager&MockObject $groupManager;
	private IRequest&MockObject $request;
	private IUserManager&MockObject $userManager;
	private SpaceManager&MockObject $spaceManager;
	private string $appName;
	private WorkspaceApiOcsController $controller;
	
	public function setUp(): void {
		parent::setUp();

		$this->appName = 'workspace';
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->request = $this->createMock(IRequest::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->userManager = $this->createMock(IUserManager::class);

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
			'userCount' => 0,
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

		$expected = new DataResponse([], Http::STATUS_OK);
				
		$actual = $this->controller->removeUsersFromGroup($id, $gid, $uids);

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getStatus(), $actual->getStatus());
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
		$this->expectExceptionMessage("These users not exist in your Nextcloud instance:\n- user2\n- user4");

		$this->controller->removeUsersFromGroup($id, $gid, $uids);
	}
}
