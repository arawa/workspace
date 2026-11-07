<?php

namespace OCA\Workspace\Tests\Unit\Service\Group;

use OCA\Workspace\Service\Group\GroupFormatter;
use OCP\IGroup;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class GroupFormatterTest extends TestCase {
	private function group(string $gid, string $displayName): MockObject&IGroup {
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn($gid);
		$group->method('getDisplayName')->willReturn($displayName);
		$group->method('getBackendNames')->willReturn(['LDAP']);

		return $group;
	}

	private function user(bool $enabled): IUser {
		$user = $this->createMock(IUser::class);
		$user->method('isEnabled')->willReturn($enabled);

		return $user;
	}

	public function testFormatGroupsWithoutUsersCountNeverLoadsMembers(): void {
		$group = $this->group('Robots 0', 'Robots 0');
		$group->expects($this->never())->method('count');
		$group->expects($this->never())->method('getUsers');

		$this->assertSame(
			[
				'Robots 0' => [
					'gid' => 'Robots 0',
					'displayName' => 'Robots 0',
					'types' => ['LDAP'],
					'slug' => 'Robots%25200',
				],
			],
			GroupFormatter::formatGroupsWithoutUsersCount([$group])
		);
	}

	public function testFormatNewGroupHasNoUsersWithoutCounting(): void {
		$group = $this->group('marketing', 'Marketing');
		$group->expects($this->never())->method('count');
		$group->expects($this->never())->method('getUsers');

		$this->assertSame(
			[
				'gid' => 'marketing',
				'displayName' => 'Marketing',
				'types' => ['LDAP'],
				'slug' => 'marketing',
				'usersCount' => 0,
			],
			GroupFormatter::formatNewGroup($group)
		);
	}

	public function testFormatGroupsCountsOnlyEnabledUsersOfAddedGroups(): void {
		$group = $this->group('marketing', 'Marketing');
		$group->expects($this->never())->method('count');
		$group->method('getUsers')->willReturn([
			$this->user(true),
			$this->user(false),
			$this->user(true),
		]);

		$actual = GroupFormatter::formatGroups([$group]);

		$this->assertSame(2, $actual['marketing']['usersCount']);
	}

	public function testFormatGroupsUsesBackendCountForWorkspaceGroups(): void {
		$group = $this->group('SPACE-U-1', 'U-Espace01');
		$group->method('count')->willReturn(5);
		$group->expects($this->never())->method('getUsers');

		$actual = GroupFormatter::formatGroups([$group]);

		$this->assertSame(5, $actual['SPACE-U-1']['usersCount']);
	}

	public function testFormatGroupsCastsUncountableWorkspaceGroupToZero(): void {
		$group = $this->group('SPACE-U-1', 'U-Espace01');
		$group->method('count')->willReturn(false);

		$actual = GroupFormatter::formatGroups([$group]);

		$this->assertSame(0, $actual['SPACE-U-1']['usersCount']);
	}
}
