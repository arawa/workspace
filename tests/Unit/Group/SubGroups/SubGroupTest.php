<?php

namespace OCA\Workspace\Tests\Unit\Group\SubGroups;

use OCA\Workspace\Exceptions\GroupException;
use OCA\Workspace\Group\SubGroups\SubGroup;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\IGroup;
use OCP\IGroupManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SubGroupTest extends TestCase {
	private IGroupManager&MockObject $groupManager;
	private LoggerInterface&MockObject $logger;
	private SubGroup $subGroup;

	public function setUp(): void {
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->subGroup = new SubGroup($this->groupManager, $this->logger);
	}

	public function testCreate(): void {
		$groupname = 'HR';
		$id = 1;
		$spacename = 'Espace01';

		$gid = sprintf('%s%s-%s', SubGroup::PREFIX_GID, $groupname, $id);
		$displayName = sprintf('%s%s-%s', SubGroup::PREFIX_DISPLAY_NAME, $groupname, $spacename);

		$this->groupManager
			->expects($this->once())
			->method('search')
			->with($displayName)
			->willReturn([])
		;
		
		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn(null)
		;

		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with($gid)
			->willReturn($group)
		;

		$group
			->expects($this->once())
			->method('setDisplayName')
			->with($displayName)
		;

		$actual = $this->subGroup->create($groupname, $id, $spacename);

		$this->assertInstanceOf(IGroup::class, $actual, "It's not the same instance.");
	}

	public function testCreateWithDuplicatedGid(): void {
		$groupname = 'HR';
		$id = 1;
		$spacename = 'Espace01';

		$gidToSet = sprintf('%s%s-%s', SubGroup::PREFIX_GID, $groupname . "1", $id);
		$displayName = sprintf('%s%s-%s', SubGroup::PREFIX_DISPLAY_NAME, "$groupname", $spacename);

		$existingGroup = $this->createMock(IGroup::class);
		$group = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('search')
			->with($displayName)
			->willReturn([])
		;

		$this->groupManager
			->expects($this->exactly(2))
			->method('get')
			->willReturn(
				$this->onConsecutiveCalls($existingGroup, null)
			)
		;

		$this->groupManager
			->expects($this->once())
			->method('createGroup')
			->with($gidToSet)
			->willReturn($group)
		;

		$group
			->expects($this->once())
			->method('setDisplayName')
			->with($displayName)
		;

		$actual = $this->subGroup->create($groupname, $id, $spacename);

		$this->assertInstanceOf(IGroup::class, $actual, "It's not the same instance.");
	}

	public function testCreateWithDuplicatedDisplayname(): void {
		$groupname = 'HR';
		$id = 1;
		$spacename = 'Espace01';

		$displayName = sprintf('%s%s-%s', SubGroup::PREFIX_DISPLAY_NAME, "$groupname", $spacename);

		$groupDuplicated = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->willReturn(
				$this->onConsecutiveCalls($groupDuplicated)
			)
		;
		
		$this->groupManager
			->expects($this->once())
			->method('search')
			->with($displayName)
			->willReturn([$groupDuplicated])
		;

		$groupDuplicated
			->expects($this->once())
			->method('getDisplayName')
			->willReturn($displayName)
		;

		$this->expectException(GroupException::class);
		$this->expectExceptionMessage("The group with the display name $displayName already exists.");
		
		$this->subGroup->create($groupname, $id, $spacename);
	}
}
