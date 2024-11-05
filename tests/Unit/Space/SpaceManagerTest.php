<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Arawa
 *
 * @author 2024 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Exceptions\AbstractNotification;
use OCA\Workspace\Exceptions\BadRequestException;
use OCA\Workspace\Exceptions\WorkspaceNameExistException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\ColorCode;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Space\SpaceManager;
use OCP\IGroup;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class SpaceManagerTest extends TestCase {

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject&GroupfolderHelper
	 */
	private GroupfolderHelper $folderHelper;

	private MockObject&RootFolder $rootFolder;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject&WorkspaceCheckService
	 */
	private WorkspaceCheckService $workspaceCheck;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject&UserGroup
	 */
	private UserGroup $userGroup;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject&SpaceMapper
	 */
	private SpaceMapper $spaceMapper;

	/**
	 * @var \PHPUnit\Framework\MockObject\MockObject&WorkspaceManagerGroup
	 */
	private WorkspaceManagerGroup $workspaceManagerGroup;

	private \PHPUnit\Framework\MockObject\MockObject&ColorCode $colorCode;

	private SpaceManager $spaceManager;

	public function setUp(): void {
		parent::setUp();

		$this->folderHelper = $this->createMock(GroupfolderHelper::class);
		$this->rootFolder = $this->createMock(RootFolder::class);
		$this->workspaceCheck = $this->createMock(WorkspaceCheckService::class);
		$this->userGroup = $this->createMock(UserGroup::class);
		$this->spaceMapper = $this->createMock(SpaceMapper::class);
		$this->workspaceManagerGroup = $this->createMock(WorkspaceManagerGroup::class);
		$this->colorCode = $this->createMock(ColorCode::class);

		$this->spaceManager = new SpaceManager(
			$this->folderHelper,
			$this->rootFolder,
			$this->workspaceCheck,
			$this->userGroup,
			$this->spaceMapper,
			$this->workspaceManagerGroup,
			$this->colorCode
		);
	}

	public function testArrayAfterCreatedTheEspace01Workspace(): void {
		$this->folderHelper
			->expects($this->once())
			->method('createFolder')
			->with('Espace01')
			->willReturn(1)
		;

		$this->rootFolder->
			expects($this->once())
				->method('getRootFolderStorageId')
				->willReturn(1)
		;

		$this->folderHelper
			->expects($this->once())
			->method('getFolder')
			->with(1, 1)
			->willReturn([
				'id' => 1,
				'mount_point' => 'Espace01',
				'groups' => [
					'SPACE-GE-1' => 31,
					'SPACE-U-1' => 31,
				],
				'quota' => -3,
				'size' => 0,
				'acl' => true,
				'manage' => [
					0 => [
						'type' => 'group',
						'id' => 'SPACE-GE-1',
						'displayname' => 'WM-Espace01',
					],
				],
				'group_details' => [
					'SPACE-GE-1' => [
						'displayName' => 'SPACE-GE-1',
						'permissions' => 31,
						'type' => 'group',
					],
					'SPACE-U-1' => [
						'displayName' => 'SPACE-U-1',
						'permissions' => 31,
						'type' => 'group',
					],
				],
			])
		;

		$workspaceManagerGroupMock = $this->createMock(IGroup::class);
		$workspaceManagerGroupMock
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-GE-1')
		;
		$workspaceManagerGroupMock
			->expects($this->once())
			->method('getDisplayName')
			->willReturn('WM-Espace01')
		;

		$userGroupMock = $this->createMock(IGroup::class);
		$userGroupMock
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-U-1')
		;
		$userGroupMock
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('U-Espace01')
		;

		$this->workspaceManagerGroup
			->expects($this->any())
			->method('create')
			->willReturn($workspaceManagerGroupMock)
		;

		$this->userGroup
			->expects($this->once())
			->method('create')
			->willReturn($userGroupMock)
		;

		$this->colorCode
			->expects($this->once())
			->method('generate')
			->willReturn('#a50b1c')
		;

		$space = $this->spaceManager->create('Espace01');

		$this->assertEquals(
			$space,
			[
				'name' => 'Espace01',
				'id_space' => null,
				'folder_id' => 1,
				'color' => '#a50b1c',
				'groups' => [
					'SPACE-GE-1' => [
						'gid' => 'SPACE-GE-1',
						'displayName' => 'WM-Espace01',
					],
					'SPACE-U-1' => [
						'gid' => 'SPACE-U-1',
						'displayName' => 'U-Espace01',
					],
				],
				'quota' => -3,
				'size' => 0,
				'acl' => true,
				'manage' => [
					0 => [
						'type' => 'group',
						'id' => 'SPACE-GE-1',
						'displayname' => 'WM-Espace01',
					],
				],
			]
		);
	}

	public function testBlankException(): void {
		$this->expectException(BadRequestException::class);
		$this->expectExceptionMessage('spaceName must be provided');

		$this->spaceManager->create('');
	}

	public function testContainSpecialCharInTheWorkspaceName(): void {
		$this->expectException(BadRequestException::class);
		$this->expectExceptionMessage('Your Workspace name must not contain the following characters: ' . implode(' ', str_split(WorkspaceCheckService::CHARACTERS_SPECIAL)));

		$this->workspaceCheck
			->expects($this->once())
			->method('containSpecialChar')
			->willReturn(true)
		;

		$this->spaceManager->create('Esp@ce01');
	}

	public function testWorkspaceAlreadyExist(): void {
		$this->expectException(WorkspaceNameExistException::class);
		$this->expectExceptionMessage("This space or groupfolder already exist. Please, input another space.\nIf \"toto\" space exist, you cannot create the \"tOTo\" space.\nMake sure you the groupfolder doesn't exist.");

		$this->workspaceCheck
			->expects($this->once())
			->method('isExist')
			->willReturn(true)
		;

		try {
			$this->spaceManager->create('Espace01');
		} catch (\Exception|AbstractNotification $e) {
			$this->assertInstanceOf(\Exception::class, $e);
			$this->assertInstanceOf(AbstractNotification::class, $e);
			$this->assertEquals('Error - Duplicate space name', $e->getTitle());
			$this->assertEquals("This space or groupfolder already exist. Please, input another space.\nIf \"toto\" space exist, you cannot create the \"tOTo\" space.\nMake sure you the groupfolder doesn't exist.", $e->getMessage());
			throw $e;
		}
	}
}
