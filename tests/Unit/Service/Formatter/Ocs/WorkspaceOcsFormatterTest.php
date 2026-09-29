<?php

namespace OCA\Workspace\Tests\Unit\Service\Formatter\Ocs;

use OCA\Workspace\Service\Formatter\Ocs\WorkspaceOcsFormatter;
use OCA\Workspace\Service\Group\WorkspaceGroupsResolver;
use OCP\IGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WorkspaceOcsFormatterTest extends TestCase {
	private MockObject&WorkspaceGroupsResolver $groupsResolver;
	private WorkspaceOcsFormatter $formatter;

	private const WORKSPACE = [
		'id' => 1,
		'groupfolder_id' => 4,
		'name' => 'Espace01',
		'color_code' => '#46221f',
	];

	public function setUp(): void {
		$this->groupsResolver = $this->createMock(WorkspaceGroupsResolver::class);
		$this->formatter = new WorkspaceOcsFormatter($this->groupsResolver);
	}

	private function group(string $gid, string $displayName, int $count): IGroup {
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn($gid);
		$group->method('getDisplayName')->willReturn($displayName);
		$group->method('getBackendNames')->willReturn(['Database']);
		$group->method('count')->willReturn($count);
		$group->method('getUsers')->willReturn([]);

		return $group;
	}

	public function testMergesGroupfolderAndWorkspace(): void {
		$manager = $this->group('SPACE-GE-1', 'WM-Espace01', 1);
		$user = $this->group('SPACE-U-1', 'U-Espace01', 3);
		$added = $this->group('marketing', 'Marketing', 0);

		$this->groupsResolver
			->expects($this->once())
			->method('resolve')
			->with(['SPACE-GE-1', 'SPACE-U-1', 'marketing'])
			->willReturn([
				'workspaceGroups' => [$manager, $user],
				'addedGroups' => [$added],
				'userGroup' => $user,
			]);

		$actual = $this->formatter->format(self::WORKSPACE, [
			'id' => 4,
			'mount_point' => 'Espace01',
			'groups' => ['SPACE-GE-1' => 31, 'SPACE-U-1' => 31, 'marketing' => 31],
			'quota' => -3,
			'acl' => true,
			'manage' => [],
		]);

		// The workspace row wins over the groupfolder on conflicting keys.
		$this->assertSame(1, $actual['id']);
		$this->assertSame(4, $actual['groupfolder_id']);
		$this->assertSame('Espace01', $actual['mount_point']);
		$this->assertSame('#46221f', $actual['color_code']);
		$this->assertTrue($actual['acl']);
		$this->assertSame(3, $actual['usersCount']);
		$this->assertSame(['SPACE-GE-1', 'SPACE-U-1'], array_keys($actual['groups']));
		$this->assertEquals((object)[
			'marketing' => [
				'gid' => 'marketing',
				'displayName' => 'Marketing',
				'types' => ['Database'],
				'usersCount' => 0,
				'slug' => 'marketing',
			],
		], $actual['added_groups']);
	}

	public function testWithoutGroupfolder(): void {
		$this->groupsResolver
			->expects($this->once())
			->method('resolve')
			->with([])
			->willReturn(['workspaceGroups' => [], 'addedGroups' => [], 'userGroup' => null]);

		$actual = $this->formatter->format(self::WORKSPACE, null);

		$this->assertEquals(self::WORKSPACE + [
			'groups' => [],
			'added_groups' => (object)[],
		], $actual);
	}
}
