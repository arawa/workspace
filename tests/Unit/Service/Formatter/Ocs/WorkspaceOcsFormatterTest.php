<?php

namespace OCA\Workspace\Tests\Unit\Service\Formatter\Ocs;

use OCA\Workspace\Service\Formatter\Ocs\WorkspaceOcsFormatter;
use OCA\Workspace\Service\Group\WorkspaceGroupsResolver;
use OCP\Files\Cache\ICacheEntry;
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

	/**
	 * Mirrors FolderWithMappingsAndCache::toArray(), undocumented keys included.
	 */
	private function folderInfo(): array {
		$rootCacheEntry = $this->createMock(ICacheEntry::class);
		$rootCacheEntry->method('getSize')->willReturn(2048);

		return [
			'id' => 4,
			'mount_point' => 'Espace01',
			'quota' => -3,
			'acl' => true,
			'acl_default_no_permission' => false,
			'storage_id' => 3,
			'root_id' => 1493,
			'root_cache_entry' => $rootCacheEntry,
			'groups' => ['SPACE-GE-1' => 31, 'SPACE-U-1' => 31, 'marketing' => 31],
			'manage' => [
				['type' => 'group', 'id' => 'SPACE-GE-1', 'displayname' => 'WM-Espace01'],
			],
			'options' => ['separate-storage' => true],
			'team_circle_id' => null,
		];
	}

	private function formattedGroup(string $gid, string $displayName, int $usersCount): array {
		return [
			'gid' => $gid,
			'displayName' => $displayName,
			'types' => ['Database'],
			'usersCount' => $usersCount,
			'slug' => $gid,
		];
	}

	public function testReturnsOnlyTheDocumentedKeys(): void {
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

		$actual = $this->formatter->format(self::WORKSPACE, $this->folderInfo());

		$this->assertEquals([
			'id' => 1,
			'mount_point' => 'Espace01',
			'groups' => (object)[
				'SPACE-GE-1' => $this->formattedGroup('SPACE-GE-1', 'WM-Espace01', 1),
				'SPACE-U-1' => $this->formattedGroup('SPACE-U-1', 'U-Espace01', 3),
			],
			'quota' => -3,
			'size' => 2048,
			'acl' => true,
			'manage' => [
				['type' => 'group', 'id' => 'SPACE-GE-1', 'displayname' => 'WM-Espace01'],
			],
			'groupfolder_id' => 4,
			'name' => 'Espace01',
			'color_code' => '#46221f',
			'usersCount' => 3,
			'added_groups' => (object)[
				'marketing' => $this->formattedGroup('marketing', 'Marketing', 0),
			],
		], $actual);
	}

	public function testWithoutUserGroupCountsNoUsers(): void {
		$this->groupsResolver
			->method('resolve')
			->willReturn(['workspaceGroups' => [], 'addedGroups' => [], 'userGroup' => null]);

		$actual = $this->formatter->format(self::WORKSPACE, $this->folderInfo());

		$this->assertSame(WorkspaceOcsFormatter::NO_USERS, $actual['usersCount']);
		$this->assertEquals((object)[], $actual['groups']);
		$this->assertEquals((object)[], $actual['added_groups']);
	}
}
