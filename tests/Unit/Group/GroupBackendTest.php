<?php

namespace OCA\Workspace\Tests\Unit\Group;

use OCA\Workspace\Group\GroupBackend;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupUsersCounter;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupBackendTest extends TestCase {
	private IGroupManager&MockObject $groupManager;
	private IUserManager&MockObject $userManager;
	private ConnectedGroupsService&MockObject $connectedGroups;
	private GroupUsersCounter&MockObject $groupUsersCounter;
	private GroupBackend $backend;

	public function setUp(): void {
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->connectedGroups = $this->createMock(ConnectedGroupsService::class);
		$this->groupUsersCounter = $this->createMock(GroupUsersCounter::class);

		$this->backend = new GroupBackend(
			$this->groupManager,
			$this->userManager,
			$this->connectedGroups,
			$this->groupUsersCounter,
		);
	}

	private function group(string $gid): IGroup&MockObject {
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn($gid);
		$group->expects($this->never())->method('getUsers');

		return $group;
	}

	public function testCountUsersInGroupWithoutConnectedGroups(): void {
		$this->connectedGroups
			->method('getConnectedGroupsToSpaceGroup')
			->with('SPACE-U-1')
			->willReturn(null)
		;

		$this->groupUsersCounter
			->expects($this->never())
			->method('getUids')
		;

		$this->assertSame(0, $this->backend->countUsersInGroup('SPACE-U-1'));
	}

	public function testCountUsersInGroupCountsConnectedUsersNotAlreadyMembers(): void {
		$hr = $this->group('HR');
		$it = $this->group('IT');
		$spaceGroup = $this->group('SPACE-U-1');

		$this->connectedGroups
			->method('getConnectedGroupsToSpaceGroup')
			->with('SPACE-U-1')
			->willReturn([$hr, $it])
		;

		$this->groupManager
			->method('get')
			->with('SPACE-U-1')
			->willReturn($spaceGroup)
		;

		$this->groupUsersCounter
			->method('getEnabledUids')
			->willReturnMap([
				[$hr, ['alice' => true, 'bob' => true]],
				[$it, ['bob' => true, 'carol' => true]],
			])
		;

		// alice is already a direct member, counted by the database backend.
		$this->groupUsersCounter
			->method('getUids')
			->with($spaceGroup)
			->willReturnCallback(function () {
				// While listing the direct members, this backend must not add the connected users.
				$this->assertSame([], $this->backend->usersInGroup('SPACE-U-1'));

				return ['alice' => true, 'zoe' => true];
			})
		;

		$this->assertSame(2, $this->backend->countUsersInGroup('SPACE-U-1'));
	}
}
