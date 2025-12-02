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

use Mockery;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Exceptions\BadRequestException;
use OCA\Workspace\Exceptions\NotFoundException;
use OCA\Workspace\Exceptions\SpacenameExistException;
use OCA\Workspace\Exceptions\WorkspaceNameSpecialCharException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Group\AddedGroups\AddedGroups;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Group\Admin\AdminUserGroup;
use OCA\Workspace\Group\SubGroups\SubGroup;
use OCA\Workspace\Group\User\UserGroup as UserWorkspaceGroup;
use OCA\Workspace\Helper\FolderStorageManagerHelper;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\ColorCode;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SpaceManagerTest extends TestCase {

	private MockObject&AddedGroups $addedGroups;
	private MockObject&AdminGroup $adminGroup;
	private MockObject&AdminUserGroup $adminUserGroup;
	private MockObject&ColorCode $colorCode;
	private MockObject&ConnectedGroupsService $conntectedGroupService;
	private MockObject&GroupfolderHelper $folderHelper;
	private MockObject&IGroupManager $groupManager;
	private MockObject&IUserManager $userManager;
	private MockObject&LoggerInterface $logger;
	private MockObject&FolderStorageManagerHelper $folderStorageManagerHelper;
	private MockObject&RootFolder $rootFolder;
	private MockObject&SpaceMapper $spaceMapper;
	private MockObject&SubGroup $subGroup;
	private MockObject&UserFormatter $userFormatter;
	private MockObject&UserGroup $userGroup;
	private MockObject&UserService $userService;
	private MockObject&UserWorkspaceGroup $userWorkspaceGroup;
	private MockObject&WorkspaceCheckService $workspaceCheck;
	private MockObject&WorkspaceManagerGroup $workspaceManagerGroup;
	private MockObject&WorkspaceService $workspaceService;

	private SpaceManager $spaceManager;

	private const CURRENT_USER_IS_GENERAL_MANAGER = true;

	public function setUp(): void {
		parent::setUp();

		$this->addedGroups = $this->createMock(AddedGroups::class);
		$this->adminGroup = $this->createMock(AdminGroup::class);
		$this->adminUserGroup = $this->createMock(AdminUserGroup::class);
		$this->colorCode = $this->createMock(ColorCode::class);
		$this->conntectedGroupService = $this->createMock(ConnectedGroupsService::class);
		$this->folderHelper = $this->createMock(GroupfolderHelper::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->folderStorageManagerHelper = $this->createMock(FolderStorageManagerHelper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->rootFolder = $this->createMock(RootFolder::class);
		$this->spaceMapper = $this->createMock(SpaceMapper::class);
		$this->subGroup = $this->createMock(SubGroup::class);
		$this->userFormatter = $this->createMock(UserFormatter::class);
		$this->userGroup = $this->createMock(UserGroup::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userService = $this->createMock(UserService::class);
		$this->userWorkspaceGroup = $this->createMock(UserWorkspaceGroup::class);
		$this->workspaceCheck = $this->createMock(WorkspaceCheckService::class);
		$this->workspaceManagerGroup = $this->createMock(WorkspaceManagerGroup::class);
		$this->workspaceService = $this->createMock(WorkspaceService::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->spaceManager = new SpaceManager(
			$this->folderHelper,
			$this->rootFolder,
			$this->workspaceCheck,
			$this->userGroup,
			$this->adminGroup,
			$this->adminUserGroup,
			$this->addedGroups,
			$this->folderStorageManagerHelper,
			$this->subGroup,
			$this->userManager,
			$this->userWorkspaceGroup,
			$this->spaceMapper,
			$this->conntectedGroupService,
			$this->logger,
			$this->userFormatter,
			$this->userService,
			$this->groupManager,
			$this->workspaceManagerGroup,
			$this->workspaceService,
			$this->colorCode
		);
	}

	public function tearDown(): void {
		Mockery::close();
	}

	public function testFindAWorkspaceForOcsController(): void {
		$spaceId = 4;
		$folderId = 4;

		/** @var Space&MockObject */
		$space = $this->createMock(Space::class);

		$groupfolder = [
			'id' => 4,
			'mount_point' => 'Espace04',
			'groups' => [
				'SPACE-GE-4' => [
					'displayName' => 'WM-Espace04',
					'permissions' => 31,
					'type' => 'group',
				],
				'SPACE-U-4' => [
					'displayName' => 'U-Espace04',
					'permissions' => 31,
					'type' => 'group',
				],
			],
			'quota' => -3,
			'size' => 0,
			'acl' => true,
			'manage' => [
				[
					'type' => 'group',
					'id' => 'SPACE-GE-4',
					'displayname' => 'WM-Espace04',
				],
			]
		];

		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($spaceId)
			->willReturn($space)
		;

		$space
			->expects($this->once())
			->method('getGroupfolderId')
			->willReturn($folderId)
		;

		$space
			->expects($this->once())
			->method('getSpaceId')
			->willReturn($spaceId)
		;

		$this->rootFolder
			->expects($this->any())
			->method('getRootFolderStorageId')
			->willReturn(2)
		;

		$folderDefinition = $this->createMock('OCA\GroupFolders\Folder\FolderWithMappingsAndCache');

		$this->folderHelper
			->expects($this->once())
			->method('getFolder')
			->willReturn($folderDefinition)
		;

		$folderDefinition
			->expects($this->once())
			->method('toArray')
			->willReturn($groupfolder)
		;


		$space
			->expects($this->once())
			->method('jsonSerialize')
			->willReturn([
				'id' => 4,
				'groupfolder_id' => 4,
				'name' => 'Espace04',
				'color_code' => '#93b250',
			])
		;

		$groupUser = $this->createMock(IGroup::class);
		$groupWorkspaceManagerUser = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->any())
			->method('get')
			->willReturn($groupUser, $groupWorkspaceManagerUser)
		;

		$groupUser
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-U-4')
		;
		$groupUser
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('U-Espace04')
		;
		$groupUser
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;
		$groupUser
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-GE-4')
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('WM-Espace04')
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupFormatter = Mockery::mock(GroupFormatter::class);
		$groupFormatter
			->shouldReceive('formatGroups')
			->with([$groupUser, $groupWorkspaceManagerUser])
			->andReturn([
				'SPACE-GE-4' => [
					'gid' => 'SPACE-GE-4',
					'displayName' => 'WM-Espace04',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-GE-4'
				],
				'SPACE-U-4' => [
					'gid' => 'SPACE-U-4',
					'displayName' => 'U-Espace04',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-U-4'
				]
			])
		;

		$this->adminGroup
			->expects($this->once())
			->method('getUsersFormatted')
			->with($groupfolder, $space)
			->willReturn([])
		;

		$actual = $this->spaceManager->get($spaceId);

		$expected = [
			'id' => 4,
			'mount_point' => 'Espace04',
			'groups' => [
				'SPACE-GE-4' => [
					'gid' => 'SPACE-GE-4',
					'displayName' => 'WM-Espace04',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-GE-4'
				],
				'SPACE-U-4' => [
					'gid' => 'SPACE-U-4',
					'displayName' => 'U-Espace04',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-U-4'
				]
			],
			'quota' => -3,
			'size' => 0,
			'acl' => true,
			'manage' => [
				[
					'type' => 'group',
					'id' => 'SPACE-GE-4',
					'displayname' => 'WM-Espace04'
				]
			],
			'groupfolder_id' => 4,
			'name' => 'Espace04',
			'color_code' => '#93b250',
			'users' => [],
			'usersCount' => 0,
			'added_groups' => (object)[]
		];

		$this->assertEquals($expected, $actual);
		$this->assertIsArray($actual);
	}


	public function testFindAWorkspaceAndReturnNull(): void {
		$spaceId = 4;

		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($spaceId)
			->willReturn(null)
		;

		$actual = $this->spaceManager->get($spaceId);

		$this->assertNull($actual);
	}


	public function testThrowsNotFoundExceptionWhenGettingWorkspaceWithGroupfolderReturningFalse(): void {
		$spaceId = 4;
		$folderId = 4;

		/** @var Space&MockObject */
		$space = $this->createMock(Space::class);

		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($spaceId)
			->willReturn($space)
		;

		$space
			->expects($this->any())
			->method('getGroupfolderId')
			->willReturn($folderId)
		;

		$this->rootFolder
			->expects($this->any())
			->method('getRootFolderStorageId')
			->willReturn(2)
		;

		$this->folderHelper
			->expects($this->once())
			->method('getFolder')
			->willReturn(null)
		;

		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage("Failed loading groupfolder with the folderId {$folderId}");

		$this->spaceManager->get($spaceId);
	}

	public function testThrowsNotFoundExceptionWhenGettingWorkspaceWithGroupfolderReturningNull(): void {
		$spaceId = 4;
		$folderId = 4;

		/** @var Space&MockObject */
		$space = $this->createMock(Space::class);

		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($spaceId)
			->willReturn($space)
		;

		$space
			->expects($this->any())
			->method('getGroupfolderId')
			->willReturn($folderId)
		;

		$this->rootFolder
			->expects($this->any())
			->method('getRootFolderStorageId')
			->willReturn(2)
		;

		$this->folderHelper
			->expects($this->once())
			->method('getFolder')
			->willReturn(null)
		;

		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage("Failed loading groupfolder with the folderId {$folderId}");

		$this->spaceManager->get($spaceId);

	}

	public function testArrayAfterCreatedTheEspace01Workspace(): void {
		$groupfolder = [
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
		];

		$this->folderHelper
			->expects($this->once())
			->method('createFolder')
			->with('Espace01')
			->willReturn(1)
		;

		$this->rootFolder
			->expects($this->once())
			->method('getRootFolderStorageId')
			->willReturn(1)
		;

		$folderDefinition = $this->createMock('OCA\GroupFolders\Folder\FolderWithMappingsAndCache');

		$this->folderHelper
			->expects($this->once())
			->method('getFolder')
			->with(1, 1)
			->willReturn($folderDefinition)
		;

		$folderDefinition
			->expects($this->once())
			->method('toArray')
			->willReturn($groupfolder)
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
				'id' => null,
				'id_space' => null,
				'folder_id' => 1,
				'color' => '#a50b1c',
				'groups' => [
					'SPACE-GE-1' => [
						'gid' => 'SPACE-GE-1',
						'displayName' => 'WM-Espace01',
						'types' => [],
						'usersCount' => 0,
						'slug' => 'SPACE-GE-1'
					],
					'SPACE-U-1' => [
						'gid' => 'SPACE-U-1',
						'displayName' => 'U-Espace01',
						'types' => [],
						'usersCount' => 0,
						'slug' => 'SPACE-U-1'
					],
				],
				'added_groups' => (object)[],
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
				'usersCount' => 0,
			]
		);
	}

	public function testBlankException(): void {
		$this->expectException(BadRequestException::class);
		$this->expectExceptionMessage('spaceName must be provided');

		$this->spaceManager->create('');
	}

	public function testContainSpecialCharInTheWorkspaceName(): void {
		$this->expectException(WorkspaceNameSpecialCharException::class);

		$this->workspaceCheck
			->expects($this->once())
			->method('containSpecialChar')
			->willReturn(true)
		;

		$this->spaceManager->create('Esp@ce01');
	}

	public function testWorkspaceAlreadyExist(): void {
		$this->expectException(SpacenameExistException::class);
		$this->expectExceptionCode(Http::STATUS_CONFLICT);

		$this->workspaceCheck
			->expects($this->once())
			->method('isExist')
			->willReturn(true)
		;

		$this->spaceManager->create('Espace01');
	}

	private function createSubGroup(
		int $id,
		int $groupfolderId,
		string $spacename,
		string $gid,
		string $groupname): IGroup {

		$space = $this->createMock(Space::class);
		$group = $this->createMock(IGroup::class);

		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($id)
			->willReturn($space)
		;

		$space
			->expects($this->once())
			->method('getSpaceName')
			->willReturn($spacename)
		;

		$space
			->expects($this->once())
			->method('getGroupfolderId')
			->willReturn($groupfolderId)
		;

		$this->subGroup
			->expects($this->once())
			->method('create')
			->with($groupname, $id, $spacename)
			->willReturn($group)
		;

		$group
			->expects($this->exactly(2))
			->method('getGID')
			->willReturn($gid)
		;

		$this->folderHelper
			->expects($this->once())
			->method('addApplicableGroup')
			->with($groupfolderId, $gid)
		;

		$this->logger
			->expects($this->once())
			->method('info')
			->with("The subgroup {$gid} has been created within the workspace {$spacename} ({$id})")
		;

		return $this->spaceManager->createSubgroup($id, $groupname);
	}

	public function testCreateSubgroup(): void {
		$id = 1;
		$spacename = 'Espace01';
		$groupname = 'HR';

		$gid = 'SPACE-G-HR-1';
		$groupfolderId = 42;

		$actual = $this->createSubgroup($id, $groupfolderId, $spacename, $gid, $groupname);

		$this->assertInstanceOf(IGroup::class, $actual, "The createSubGroup function doesn't return a IGroup instance.");
	}

	public function testPreventDuplicateCreateSubgroup(): void {
		$id = 1;
		$spacename = 'Espace01';
		$groupname = 'HR';

		$space = $this->createMock(Space::class);

		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($id)
			->willReturn($space)
		;

		$space
			->expects($this->once())
			->method('getSpaceName')
			->willReturn($spacename)
		;

		$this->subGroup
			->expects($this->once())
			->method('create')
			->with($groupname, $id, $spacename)
			->willThrowException(new OCSBadRequestException("The group {$groupname} already exists for this workspace."))
		;

		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage("The group {$groupname} already exists for this workspace.");

		$this->spaceManager->createSubgroup($id, $groupname);
	}

	public function testCreateSubgroupWithASlash(): void {
		$id = 1;
		$spacename = 'Espace01';
		$groupname = 'HR';

		$gid = 'SPACE-G-/-1';
		$groupfolderId = 42;

		$actual = $this->createSubgroup($id, $groupfolderId, $spacename, $gid, $groupname);

		$this->assertInstanceOf(IGroup::class, $actual, "The createSubGroup function doesn't return a IGroup instance.");
	}

	public function testCreateSubgroupWithHooks(): void {
		$id = 1;
		$spacename = 'Espace01';
		$groupname = 'HR';

		$gid = 'SPACE-G-Place {42}-1';
		$groupfolderId = 42;

		$actual = $this->createSubgroup($id, $groupfolderId, $spacename, $gid, $groupname);

		$this->assertInstanceOf(IGroup::class, $actual, "The createSubGroup function doesn't return a IGroup instance.");
	}

	public function testCreateSubgroupWithBackslashes(): void {
		$id = 1;
		$spacename = 'Espace01';
		$groupname = 'HR';

		$gid = 'SPACE-G-Place \42\-1';
		$groupfolderId = 42;

		$actual = $this->createSubgroup($id, $groupfolderId, $spacename, $gid, $groupname);

		$this->assertInstanceOf(IGroup::class, $actual, "The createSubGroup function doesn't return a IGroup instance.");
	}

	public function testCreateSubgroupWithBrackets(): void {
		$id = 1;
		$spacename = 'Espace01';
		$groupname = 'HR';

		$gid = 'SPACE-G-Place (42)-1';
		$groupfolderId = 42;

		$actual = $this->createSubgroup($id, $groupfolderId, $spacename, $gid, $groupname);

		$this->assertInstanceOf(IGroup::class, $actual, "The createSubGroup function doesn't return a IGroup instance.");
	}

	public function testFindGroupsBySpaceId(): void {
		$spaceId = 1;
		$groupfolder
			= [
				'id' => 1,
				'mount_point' => 'Espace01',
				'groups' => [
					'SPACE-GE-1' => [
						'displayName' => 'WM-Espace01',
						'permissions' => 31,
						'type' => 'group',
					],
					'SPACE-U-1' => [
						'displayName' => 'U-Espace01',
						'permissions' => 31,
						'type' => 'group',
					],
				],
				'quota' => -3,
				'size' => 0,
				'acl' => true,
				'manage' => [
					[
						'type' => 'group',
						'id' => 'SPACE-GE-1',
						'displayname' => 'WM-Espace01',
					],
				]
			]
		;

		/** @var Space&MockObject */
		$space = $this->createMock(Space::class);

		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($spaceId)
			->willReturn($space)
		;

		$space
			->expects($this->once())
			->method('getGroupfolderId')
			->willReturn(1)
		;

		$this->rootFolder
			->expects($this->any())
			->method('getRootFolderStorageId')
			->willReturn(2)
		;

		$folderDefinition = $this->createMock('OCA\GroupFolders\Folder\FolderWithMappingsAndCache');

		$this->folderHelper
			->expects($this->once())
			->method('getFolder')
			->willReturn($folderDefinition)
		;

		$folderDefinition
			->expects($this->once())
			->method('toArray')
			->willReturn($groupfolder)
		;

		$groupUser = $this->createMock(IGroup::class);
		$groupWorkspaceManagerUser = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->any())
			->method('get')
			->willReturn($groupUser, $groupWorkspaceManagerUser)
		;

		$groupUser
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-U-1')
		;
		$groupUser
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('U-Espace01')
		;
		$groupUser
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;
		$groupUser
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-GE-1')
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('WM-Espace01')
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupFormatter = Mockery::mock(GroupFormatter::class);
		$groupFormatter
			->shouldReceive('formatGroups')
			->with([$groupUser, $groupWorkspaceManagerUser])
			->andReturn([
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
			])
		;

		$actual = $this->spaceManager->findGroupsBySpaceId($spaceId);

		$expected = [
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
		];

		$this->assertEquals($expected, $actual);
	}

	public function testFindAll(): void {
		$groupfolder
			= [
				'id' => 1,
				'mount_point' => 'Espace01',
				'groups' => [
					'SPACE-GE-1' => 31,
					'SPACE-U-1' => 31
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
				]
			]
		;

		$this->workspaceService
			->expects($this->once())
			->method('getAll')
			->willReturn([
				[
					'id' => 1,
					'groupfolder_id' => 1,
					'name' => 'Espace01',
					'color_code' => '#46221f'
				]
			])
		;

		$this->rootFolder
			->expects($this->any())
			->method('getRootFolderStorageId')
			->willReturn(2)
		;

		$folderDefinition = $this->createMock('OCA\GroupFolders\Folder\FolderWithMappingsAndCache');

		$this->folderHelper
			->expects($this->any())
			->method('getFolder')
			->willReturnOnConsecutiveCalls($folderDefinition)
		;

		$folderDefinition
			->expects($this->once())
			->method('toArray')
			->willReturn($groupfolder)
		;

		$groupUser = $this->createMock(IGroup::class);
		$groupUser
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-U-1')
		;
		$groupUser
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('U-Espace01')
		;
		$groupUser
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupUser
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;

		$groupWorkspaceManager = $this->createMock(IGroup::class);
		$groupWorkspaceManager
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-GE-1')
		;
		$groupWorkspaceManager
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('WM-Espace01')
		;
		$groupWorkspaceManager
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupWorkspaceManager
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;

		$this->groupManager
			->expects($this->any())
			->method('get')
			->willReturn($groupUser, $groupWorkspaceManager)
		;

		$this->userService
			->expects($this->any())
			->method('isUserGeneralAdmin')
			->willReturn(self::CURRENT_USER_IS_GENERAL_MANAGER)
		;

		$groupFormatter = Mockery::mock(GroupFormatter::class);
		$groupFormatter
			->shouldReceive('formatGroups')
			->with([$groupUser, $groupWorkspaceManager])
			->andReturn([
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
			])
		;

		$actual = $this->spaceManager->findAll();

		$expected
			= [
				[
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
					'color_code' => '#46221f',
					'usersCount' => 0,
					'added_groups' => (object)[]
				]
			]
		;

		$this->assertEquals($expected, $actual);
	}

	public function testRemoveUsersFromWorkspace(): void {
		$spaceId = 1;
		$uids = ['user1', 'user2'];

		/** @var IUser&MockObject */
		$user1 = $this->createMock(IUser::class);
		/** @var IUser&MockObject */
		$user2 = $this->createMock(IUser::class);

		$this->userManager
			->expects($this->any())
			->method('get')
			->with(
				$this->logicalOr($this->equalTo('user1'), $this->equalTo('user2'))
			)
			->willReturnOnConsecutiveCalls($user1, $user2, $user1, $user2)
		;

		/** @var IGroup&MockObject */
		$userGroup = $this->createMock(IGroup::class);
		/** @var IGroup&MockObject */
		$managerGroup = $this->createMock(IGroup::class);

		$user1
			->expects($this->once())
			->method('getUID')
			->willReturn('user1')
		;

		$user2
			->expects($this->once())
			->method('getUID')
			->willReturn('user2')
		;

		$managerGroup
			->expects($this->exactly(2))
			->method('inGroup')
			->with(
				$this->logicalOr($user1, $user2)
			)
			->willReturnOnConsecutiveCalls(false, false)
		;

		$this->groupManager
			->expects($this->any())
			->method('get')
			->willReturnCallback(function ($gid) use ($userGroup, $managerGroup) {
				if ($gid === 'SPACE-U-1') {
					return $userGroup;
				}
				if ($gid === 'SPACE-GE-1') {
					return $managerGroup;
				}
			})
		;

		$userGroup
			->expects($this->any())
			->method('inGroup')
			->with(
				$this->logicalOr($user1, $user2)
			)
			->willReturnOnConsecutiveCalls(true, true)
		;

		$userGroup
			->expects($this->any())
			->method('removeUser')
			->with(
				$this->logicalOr($user1, $user2)
			)
		;

		$managerGroup
			->expects($this->any())
			->method('removeUser')
			->with(
				$this->logicalOr($user1, $user2)
			)
		;

		/**
		 * @var MockObject&SpaceManager
		 *
		 * Mock only the get method.
		 */
		$spaceManagerPartial = $this->getMockBuilder(SpaceManager::class)
			->setConstructorArgs([
				$this->folderHelper,
				$this->rootFolder,
				$this->workspaceCheck,
				$this->userGroup,
				$this->adminGroup,
				$this->adminUserGroup,
				$this->addedGroups,
				$this->folderStorageManagerHelper,
				$this->subGroup,
				$this->userManager,
				$this->userWorkspaceGroup,
				$this->spaceMapper,
				$this->conntectedGroupService,
				$this->logger,
				$this->userFormatter,
				$this->userService,
				$this->groupManager,
				$this->workspaceManagerGroup,
				$this->workspaceService,
				$this->colorCode
			])
			->onlyMethods(['get'])
			->getMock()
		;

		$spaceManagerPartial->expects($this->once())
			->method('get')
			->willReturn([
				// other data...
				'groups' => [
					'SPACE-U-1' => [],
					'SPACE-GE-1' => [],
				]])
		;

		$spaceManagerPartial->removeUsersFromWorkspace($spaceId, $uids);
	}

	public function testAddUsersInWorkspace(): void {
		$spaceId = 1;
		$uids = ['user1', 'user2'];

		/** @var IUser&MockObject */
		$user1 = $this->createMock(IUser::class);
		/** @var IUser&MockObject */
		$user2 = $this->createMock(IUser::class);

		$this->userManager
			->expects($this->any())
			->method('get')
			->with(
				$this->logicalOr($this->equalTo('user1'), $this->equalTo('user2'))
			)
			->willReturnOnConsecutiveCalls($user1, $user2, $user1, $user2)
		;

		$groupUser = $this->createMock(IGroup::class);
		$groupWorkspaceManagerUser = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->any())
			->method('get')
			->willReturn($groupUser, $groupWorkspaceManagerUser)
		;

		$groupFormatter = Mockery::mock(UserGroup::class);
		$groupFormatter
			->shouldReceive('get')
			->with($spaceId)
			->andReturn('SPACE-U-1')
		;

		/** @var IGroup&MockObject */
		$userGroup = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->willReturn($userGroup)
		;

		$groupUser
			->expects($this->any())
			->method('addUser')
			->with($this->logicalOr($this->equalTo($user1), $this->equalTo($user2)))
		;

		$this->spaceManager->addUsersInWorkspace($spaceId, $uids);
	}

	public function testAddUserAsWorkspaceManager(): void {
		$spaceId = 4;
		$uid = 'user01';

		/** @var MockObject&IUser */
		$user = $this->createMock(IUser::class);

		$this->userManager
			->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user)
		;


		$workspaceManagerGroupMock = Mockery::mock(WorkspaceManagerGroup::class);
		$workspaceManagerGroupMock
			->shouldReceive('get')
			->with($spaceId)
			->andReturn("SPACE-GE-{$spaceId}")
		;

		$userGroupMock = Mockery::mock(UserGroup::class);
		$userGroupMock
			->shouldReceive('get')
			->with($spaceId)
			->andReturn("SPACE-U-{$spaceId}")
		;

		/** @var MockObject&IGroup */
		$managerGroup = $this->createMock(IGroup::class);
		/** @var MockObject&IGroup */
		$userGroup = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->exactly(2))
			->method('get')
			->willReturn($managerGroup, $userGroup)
		;

		$userGroup
			->expects($this->once())
			->method('inGroup')
			->willReturn(true)
		;

		$managerGroup
			->expects($this->once())
			->method('addUser')
			->with($user)
		;

		$this->adminGroup
			->expects($this->once())
			->method('addUser')
			->with($user, $spaceId)
		;

		$this->spaceManager->addUserAsWorkspaceManager($spaceId, $uid);
	}

	public function testRemoveUsersFromSubGroup(): void {
		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);

		/** @var MockObject&IGroup */
		$group = $this->createMock(IGroup::class);

		$users = [
			$user1,
			$user2,
			$user3,
		];

		$group
			->expects($this->exactly(3))
			->method('removeUser')
		;

		$this->spaceManager->removeUsersFromSubGroup($group, $users);
	}

	public function testRemoveUsersFromWorkspaceManagerGroup(): void {
		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);

		/** @var MockObject&IGroup */
		$group = $this->createMock(IGroup::class);

		$users = [
			$user1,
			$user2,
			$user3,
		];

		$group
			->expects($this->exactly(3))
			->method('inGroup')
			->willReturn(true)
		;

		$group
			->expects($this->exactly(3))
			->method('removeUser')
		;

		$this->spaceManager->removeUsersFromWorkspaceManagerGroup($group, $users);
	}

	public function testAddUsersInWorkspaceThrowsExceptionForDoesntExistUsers(): void {
		$spaceId = 1;
		$uids = ['user1', 'user2', 'user42'];

		/** @var IUser&MockObject */
		$user1 = $this->createMock(IUser::class);
		/** @var IUser&MockObject */
		$user2 = $this->createMock(IUser::class);
		/** @var IUser|null */
		$user42 = null;

		$this->userManager
			->expects($this->any())
			->method('get')
			->with(
				$this->logicalOr($this->equalTo('user1'), $this->equalTo('user2'), $this->equalTo('user42'))
			)
			->willReturnOnConsecutiveCalls($user1, $user2, $user42)
		;

		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage("These users does not exist on your Nextcloud instance : \n- user42\n");

		$this->spaceManager->addUsersInWorkspace($spaceId, $uids);
	}

	public function testRemoveUsersFromUserGroup(): void {
		$managerGroupGid = 'SPACE-GE-1';
		$userGroupGid = 'SPACE-U-1';
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
				],
				'SPACE-G-HR-1' => [
					'gid' => 'SPACE-G-HR-1',
					'displayName' => 'G-HR-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-HR-1'
				],
				'SPACE-G-Admin-1' => [
					'gid' => 'SPACE-G-Admin-1',
					'displayName' => 'G-Admin-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-Admin-1'
				],
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
			'usersCount' => 0,
			'users' => [],
			'added_groups' => []
		];

		$user01 = $this->createMock(IUser::class);
		$user02 = $this->createMock(IUser::class);
		$user03 = $this->createMock(IUser::class);
		$user04 = $this->createMock(IUser::class);

		$users = [
			$user01,
			$user02,
			$user03,
			$user04,
		];

		$managerGroup = $this->createMock(IGroup::class);
		/** @var IGroup&MockObject */
		$userGroup = $this->createMock(IGroup::class);
		$hrSubgroup = $this->createMock(IGroup::class);
		$adminSubGroup = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->exactly(4))
			->method('get')
			->with(
				$this->logicalOr(
					$this->equalTo($managerGroupGid),
					$this->equalTo($userGroupGid),
					$this->equalTo('SPACE-G-HR-1'),
					$this->equalTo('SPACE-G-Admin-1'),
				)
			)
			->willReturn(
				$managerGroup,
				$userGroup,
				$hrSubgroup,
				$adminSubGroup,
			)
		;

		$userGroup
			->expects($this->exactly(4))
			->method('removeUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user01),
					$this->equalTo($user02),
					$this->equalTo($user03),
					$this->equalTo($user04),
				)
			)
		;

		$hrSubgroup
			->expects($this->exactly(4))
			->method('removeUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user01),
					$this->equalTo($user02),
					$this->equalTo($user03),
					$this->equalTo($user04),
				)
			)
		;

		$adminSubGroup
			->expects($this->exactly(4))
			->method('removeUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user01),
					$this->equalTo($user02),
					$this->equalTo($user03),
					$this->equalTo($user04),
				)
			)
		;

		$this->spaceManager->removeUsersFromUserGroup($space, $userGroup, $users);
	}

	public function testAddUsersToGroup(): void {
		/** @var IGroup&MockObject */
		$group = $this->createMock(IGroup::class);

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user4 = $this->createMock(IUser::class);

		$users = [
			$user1,
			$user2,
			$user3,
			$user4,
		];

		$group
			->expects($this->exactly(4))
			->method('addUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user1),
					$this->equalTo($user2),
					$this->equalTo($user3),
					$this->equalTo($user4),
				)
			)
		;

		$this->spaceManager->addUsersToGroup($group, $users);
	}

	public function testAddUsersToSubGroup(): void {
		/** @var IGroup&MockObject */
		$group = $this->createMock(IGroup::class);

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user4 = $this->createMock(IUser::class);

		$users = [
			$user1,
			$user2,
			$user3,
			$user4,
		];

		$workspace = [
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
					'usersCount' => 8,
					'slug' => 'SPACE-U-1'
				],
				'SPACE-G-Talk-1' => [
					'gid' => 'SPACE-G-Talk-1',
					'displayName' => 'G-Talk-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-Talk-1'
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
			'color_code' => '#ac1a8e',
			'usersCount' => 8,
			'users' => [],
			'added_groups' => []
		];

		$group
			->expects($this->exactly(4))
			->method('addUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user1),
					$this->equalTo($user2),
					$this->equalTo($user3),
					$this->equalTo($user4),
				)
			)
		;

		$userGroup = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->once())
			->method('get')
			->willReturn($userGroup)
		;

		$group
			->expects($this->exactly(4))
			->method('addUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user1),
					$this->equalTo($user2),
					$this->equalTo($user3),
					$this->equalTo($user4),
				)
			)
		;

		$userGroup
			->expects($this->exactly(4))
			->method('addUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user1),
					$this->equalTo($user2),
					$this->equalTo($user3),
					$this->equalTo($user4),
				)
			)
		;

		$this->spaceManager->addUsersToSubGroup($workspace, $group, $users);
	}

	public function testAddUsersToSubGroupWithUserGroupDoesNotExist(): void {
		/** @var IGroup&MockObject */
		$group = $this->createMock(IGroup::class);

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user4 = $this->createMock(IUser::class);

		$users = [
			$user1,
			$user2,
			$user3,
			$user4,
		];

		$workspace = [
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
					'usersCount' => 8,
					'slug' => 'SPACE-U-1'
				],
				'SPACE-G-Talk-1' => [
					'gid' => 'SPACE-G-Talk-1',
					'displayName' => 'G-Talk-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-Talk-1'
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
			'color_code' => '#ac1a8e',
			'usersCount' => 8,
			'users' => [],
			'added_groups' => []
		];

		$userGroup = null;

		$this->groupManager
			->expects($this->once())
			->method('get')
			->willReturn($userGroup)
		;

		$this->expectException(NotFoundException::class);

		$this->spaceManager->addUsersToSubGroup($workspace, $group, $users);
	}

	public function testAddUsersToWorkspaceManagerGroup(): void {
		/** @var IGroup&MockObject */
		$group = $this->createMock(IGroup::class);

		$user1 = $this->createMock(IUser::class);
		$user2 = $this->createMock(IUser::class);
		$user3 = $this->createMock(IUser::class);
		$user4 = $this->createMock(IUser::class);

		$users = [
			$user1,
			$user2,
			$user3,
			$user4,
		];

		$workspace = [
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
					'usersCount' => 8,
					'slug' => 'SPACE-U-1'
				],
				'SPACE-G-Talk-1' => [
					'gid' => 'SPACE-G-Talk-1',
					'displayName' => 'G-Talk-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-G-Talk-1'
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
			'color_code' => '#ac1a8e',
			'usersCount' => 8,
			'users' => [],
			'added_groups' => []
		];

		$group
			->expects($this->exactly(4))
			->method('addUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user1),
					$this->equalTo($user2),
					$this->equalTo($user3),
					$this->equalTo($user4),
				)
			)
		;

		$userGroup = $this->createMock(IGroup::class);
		$workspaceManagerGroup = $this->createMock(IGroup::class);

		$userGroupGid = 'SPACE-U-1';
		$workspaceManagerGid = 'WorkspacesManagers';

		$this->groupManager
			->expects($this->exactly(2))
			->method('get')
			->with(
				$this->logicalOr(
					$this->equalTo($userGroupGid),
					$this->equalTo($workspaceManagerGid),
				)
			)
			->willReturn(
				$userGroup,
				$workspaceManagerGroup,
			)
		;

		$group
			->expects($this->exactly(4))
			->method('addUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user1),
					$this->equalTo($user2),
					$this->equalTo($user3),
					$this->equalTo($user4),
				)
			)
		;

		$userGroup
			->expects($this->exactly(4))
			->method('addUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user1),
					$this->equalTo($user2),
					$this->equalTo($user3),
					$this->equalTo($user4),
				)
			)
		;

		$workspaceManagerGroup
			->expects($this->exactly(4))
			->method('addUser')
			->with(
				$this->logicalOr(
					$this->equalTo($user1),
					$this->equalTo($user2),
					$this->equalTo($user3),
					$this->equalTo($user4),
				)
			)
		;

		$this->spaceManager->addUsersToWorkspaceManagerGroup($workspace, $group, $users);
	}

	public function testRenameWorkspace(): void {
		$spaceId = 1;
		$folderId = 10;
		$newSpaceName = 'SpaceOne';

		/**
		 * @var MockObject&SpaceManager
		 *
		 * Mock only the get method.
		 */
		$spaceManagerPartial = $this->getMockBuilder(SpaceManager::class)
			->setConstructorArgs([
				$this->folderHelper,
				$this->rootFolder,
				$this->workspaceCheck,
				$this->userGroup,
				$this->adminGroup,
				$this->adminUserGroup,
				$this->addedGroups,
				$this->folderStorageManagerHelper,
				$this->subGroup,
				$this->userManager,
				$this->userWorkspaceGroup,
				$this->spaceMapper,
				$this->conntectedGroupService,
				$this->logger,
				$this->userFormatter,
				$this->userService,
				$this->groupManager,
				$this->workspaceManagerGroup,
				$this->workspaceService,
				$this->colorCode
			])
			->onlyMethods(['get'])
			->getMock()
		;

		$spaceManagerPartial->expects($this->once())
			->method('get')
			->willReturn([
				// other data...
				'groupfolder_id' => $folderId
			])
		;

		$this->workspaceCheck
			->expects($this->once())
			->method('containSpecialChar')
			->with($newSpaceName)
			->willReturn(false)
		;

		$this->workspaceCheck
			->expects($this->once())
			->method('isExist')
			->with($newSpaceName)
			->willReturn(false)
		;

		$this->folderHelper
			->expects($this->once())
			->method('renameFolder')
			->with($folderId, $newSpaceName)
		;

		$this->spaceMapper
			->expects($this->once())
			->method('updateSpaceName')
			->with($newSpaceName, $spaceId)
		;

		$spaceManagerPartial->rename($spaceId, $newSpaceName);
	}

	public function testRenameWorkspaceWithSpecialCharacter(): void {
		$spaceId = 1;
		$folderId = 10;
		$newSpaceName = 'Space/One';

		/**
		 * @var MockObject&SpaceManager
		 *
		 * Mock only the get method.
		 */
		$spaceManagerPartial = $this->getMockBuilder(SpaceManager::class)
			->setConstructorArgs([
				$this->folderHelper,
				$this->rootFolder,
				$this->workspaceCheck,
				$this->userGroup,
				$this->adminGroup,
				$this->adminUserGroup,
				$this->addedGroups,
				$this->folderStorageManagerHelper,
				$this->subGroup,
				$this->userManager,
				$this->userWorkspaceGroup,
				$this->spaceMapper,
				$this->conntectedGroupService,
				$this->logger,
				$this->userFormatter,
				$this->userService,
				$this->groupManager,
				$this->workspaceManagerGroup,
				$this->workspaceService,
				$this->colorCode
			])
			->onlyMethods(['get'])
			->getMock()
		;

		$spaceManagerPartial->expects($this->once())
			->method('get')
			->willReturn([
				// other data...
				'groupfolder_id' => $folderId
			])
		;

		$this->workspaceCheck
			->expects($this->once())
			->method('containSpecialChar')
			->with($newSpaceName)
			->willReturn(true)
		;

		$this->expectException(WorkspaceNameSpecialCharException::class);
		$this->expectExceptionMessage('');
		$this->expectExceptionCode(Http::STATUS_BAD_REQUEST);
		$spaceManagerPartial->rename($spaceId, $newSpaceName);
	}

	public function testRenameWorkspaceWithDuplicateName(): void {
		$spaceId = 1;
		$folderId = 10;
		$newSpaceName = 'SpaceOne';

		/**
		 * @var MockObject&SpaceManager
		 *
		 * Mock only the get method.
		 */
		$spaceManagerPartial = $this->getMockBuilder(SpaceManager::class)
			->setConstructorArgs([
				$this->folderHelper,
				$this->rootFolder,
				$this->workspaceCheck,
				$this->userGroup,
				$this->adminGroup,
				$this->adminUserGroup,
				$this->addedGroups,
				$this->folderStorageManagerHelper,
				$this->subGroup,
				$this->userManager,
				$this->userWorkspaceGroup,
				$this->spaceMapper,
				$this->conntectedGroupService,
				$this->logger,
				$this->userFormatter,
				$this->userService,
				$this->groupManager,
				$this->workspaceManagerGroup,
				$this->workspaceService,
				$this->colorCode
			])
			->onlyMethods(['get'])
			->getMock()
		;

		$spaceManagerPartial->expects($this->once())
			->method('get')
			->willReturn([
				// other data...
				'groupfolder_id' => $folderId
			])
		;

		$this->workspaceCheck
			->expects($this->once())
			->method('containSpecialChar')
			->with($newSpaceName)
			->willReturn(false)
		;

		$this->workspaceCheck
			->expects($this->once())
			->method('isExist')
			->with($newSpaceName)
			->willReturn(true)
		;

		$this->expectException(SpacenameExistException::class);
		$this->expectExceptionMessage('');
		$this->expectExceptionCode(Http::STATUS_CONFLICT);
		$spaceManagerPartial->rename($spaceId, $newSpaceName);
	}
}
