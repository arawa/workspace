<?php

namespace OCA\Workspace\Tests\Unit\Service\Group;

use OCA\Workspace\Service\Group\GroupUsersCounter;
use OCP\IGroup;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupUsersCounterTest extends TestCase {
	private IUserManager&MockObject $userManager;
	private GroupUsersCounter $counter;

	public function setUp(): void {
		$this->userManager = $this->createMock(IUserManager::class);
		// Counting must never verify users one by one.
		$this->userManager->expects($this->never())->method('get');

		$this->counter = new GroupUsersCounter($this->userManager);
	}

	private function user(string $uid): IUser&MockObject {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($uid);

		return $user;
	}

	/**
	 * @param string[] $uids
	 */
	private function group(array $uids): IGroup&MockObject {
		$group = $this->createMock(IGroup::class);
		$group
			->method('searchUsers')
			->with('')
			->willReturn(array_map(fn (string $uid) => $this->user($uid), $uids))
		;
		$group->expects($this->never())->method('getUsers');

		return $group;
	}

	public function testGetUidsReturnsUidsAsKeys(): void {
		$actual = $this->counter->getUids($this->group(['alice', 'bob', '1234']));

		$this->assertSame(['alice' => true, 'bob' => true, '1234' => true], $actual);
	}

	public function testCountEnabledUsersExcludesDisabledUsers(): void {
		$group = $this->group(['alice', 'bob', 'carol']);

		$this->userManager
			->expects($this->once())
			->method('getDisabledUsers')
			->willReturn([$this->user('bob'), $this->user('dave')])
		;

		$this->assertSame(2, $this->counter->countEnabledUsers($group));
		// The disabled users list is fetched once per request.
		$this->assertSame(['alice' => true, 'carol' => true], $this->counter->getEnabledUids($group));
	}

	public function testEnabledUidsAreListedOncePerGroup(): void {
		$this->userManager->method('getDisabledUsers')->willReturn([]);

		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn('HR');
		$group
			->expects($this->once())
			->method('searchUsers')
			->willReturn([$this->user('alice')])
		;

		$this->counter->countEnabledUsers($group);

		$this->assertSame(['alice' => true], $this->counter->getEnabledUids($group));
	}
}
