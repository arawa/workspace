<?php

namespace OCA\Workspace\Tests\Unit\Service\Group;

use OCA\Workspace\Service\Group\WorkspaceGroupsResolver;
use OCP\IGroup;
use OCP\IGroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkspaceGroupsResolverTest extends TestCase {
	private MockObject&IGroupManager $groupManager;
	private MockObject&LoggerInterface $logger;
	private WorkspaceGroupsResolver $resolver;

	public function setUp(): void {
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->resolver = new WorkspaceGroupsResolver($this->groupManager, $this->logger);
	}

	private function group(string $gid, string $displayName): IGroup {
		$group = $this->createMock(IGroup::class);
		$group->method('getGID')->willReturn($gid);
		$group->method('getDisplayName')->willReturn($displayName);

		return $group;
	}

	public function testSortsWorkspaceAndAddedGroups(): void {
		$manager = $this->group('SPACE-GE-1', 'WM-Espace01');
		$user = $this->group('SPACE-U-1', 'U-Espace01');
		$subgroup = $this->group('SPACE-G-1-team', 'G-team-Espace01');
		$added = $this->group('marketing', 'Marketing');

		$this->groupManager
			->method('get')
			->willReturnMap([
				['SPACE-GE-1', $manager],
				['SPACE-U-1', $user],
				['SPACE-G-1-team', $subgroup],
				['marketing', $added],
			]);

		$actual = $this->resolver->resolve(['SPACE-GE-1', 'SPACE-U-1', 'SPACE-G-1-team', 'marketing']);

		$this->assertSame([$manager, $user, $subgroup], $actual['workspaceGroups']);
		$this->assertSame([$added], $actual['addedGroups']);
		$this->assertSame($user, $actual['userGroup']);
	}

	public function testRecognisesLegacyLocalGroupAsWorkspaceGroup(): void {
		$legacy = $this->group('legacy-gid', 'G-legacy');

		$this->groupManager->method('get')->willReturn($legacy);

		$actual = $this->resolver->resolve(['legacy-gid']);

		$this->assertSame([$legacy], $actual['workspaceGroups']);
		$this->assertSame([], $actual['addedGroups']);
		$this->assertNull($actual['userGroup']);
	}

	public function testSkipsAndWarnsAboutMissingGroups(): void {
		$user = $this->group('SPACE-U-1', 'U-Espace01');

		$this->groupManager
			->method('get')
			->willReturnMap([
				['SPACE-U-1', $user],
				['SPACE-GE-1', null],
			]);

		$this->logger
			->expects($this->once())
			->method('warning')
			->with($this->stringContains('SPACE-GE-1'));

		$actual = $this->resolver->resolve(['SPACE-GE-1', 'SPACE-U-1']);

		$this->assertSame([$user], $actual['workspaceGroups']);
		$this->assertSame($user, $actual['userGroup']);
	}

	public function testEmptyGids(): void {
		$this->groupManager->expects($this->never())->method('get');

		$this->assertSame(
			['workspaceGroups' => [], 'addedGroups' => [], 'userGroup' => null],
			$this->resolver->resolve([])
		);
	}
}
