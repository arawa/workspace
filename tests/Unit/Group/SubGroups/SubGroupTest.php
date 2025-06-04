<?php

namespace OCA\Workspace\Tests\Unit\Group\SubGroups;

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

	public function testCreateWithDuplicated(): void {
		$groupname = 'HR';
		$id = 1;
		$spacename = 'Espace01';

		$gid = sprintf('%s%s-%s', SubGroup::PREFIX_GID, $groupname, $id);

		$existingGroup = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->with($gid)
			->willReturn($existingGroup)
		;

		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage("The group {$groupname} already exists for this workspace.");

		$this->subGroup->create($groupname, $id, $spacename);
	}
}
