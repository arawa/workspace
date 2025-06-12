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
use OCP\IUser;
use OCP\IGroup;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\AppFramework\Http;
use OCA\Workspace\Db\Space;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Service\ColorCode;
use OCA\Workspace\Space\SpaceManager;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Group\Admin\AdminGroup;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Group\SubGroups\SubGroup;
use OCA\Workspace\Helper\GroupfolderHelper;
use PHPUnit\Framework\MockObject\MockObject;
use OCA\Workspace\Group\Admin\AdminUserGroup;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Group\AddedGroups\AddedGroups;
use OCA\Workspace\Exceptions\BadRequestException;
use OCA\Workspace\Exceptions\AbstractNotification;
use OCA\Workspace\Service\Group\WorkspaceManagerGroup;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Exceptions\WorkspaceNameExistException;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use OCA\Workspace\Group\User\UserGroup as UserWorkspaceGroup;
use OCP\AppFramework\OCS\OCSBadRequestException;

class SpaceManagerTest extends TestCase {

	private MockObject&GroupfolderHelper $folderHelper;

	private MockObject&RootFolder $rootFolder;

	private MockObject&WorkspaceCheckService $workspaceCheck;

	private MockObject&UserGroup $userGroup;

	private MockObject&SpaceMapper $spaceMapper;

	private MockObject&WorkspaceManagerGroup $workspaceManagerGroup;

	private MockObject&ColorCode $colorCode;

	private MockObject&AdminGroup $adminGroup;

	private MockObject&AdminUserGroup $adminUserGroup;

	private MockObject&AddedGroups $addedGroups;

	private MockObject&SubGroup $subGroup;

	private MockObject&UserWorkspaceGroup $userWorkspaceGroup;

	private MockObject&ConnectedGroupsService $conntectedGroupService;

	private MockObject&UserFormatter $userFormatter;

	private MockObject&UserService $userService;

	private MockObject&IGroupManager $groupManager;

	private MockObject&LoggerInterface $logger;

	private MockObject&IUserManager $userManager;

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
		$this->adminGroup = $this->createMock(AdminGroup::class);
		$this->adminUserGroup = $this->createMock(AdminUserGroup::class);
		$this->addedGroups = $this->createMock(AddedGroups::class);
		$this->subGroup = $this->createMock(SubGroup::class);
		$this->userWorkspaceGroup = $this->createMock(UserWorkspaceGroup::class);
		$this->conntectedGroupService = $this->createMock(ConnectedGroupsService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userFormatter = $this->createMock(UserFormatter::class);
		$this->userService = $this->createMock(UserService::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);


		$this->spaceManager = new SpaceManager(
			$this->folderHelper,
			$this->rootFolder,
			$this->workspaceCheck,
			$this->userGroup,
			$this->adminGroup,
			$this->adminUserGroup,
			$this->addedGroups,
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
			$this->colorCode
		);
	}

	public function tearDown(): void {
		Mockery::close();
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
				'userCount' => 0,
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

		$referenceMessage = "This space or groupfolder already exists. Please, use another space name.\nIf a \"toto\" space exists, you cannot create the \"tOTo\" space.\nPlease check also the groupfolder doesn't exist.";
		$this->expectException(WorkspaceNameExistException::class);
		$this->expectExceptionMessage($referenceMessage);

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
			$this->assertEquals($referenceMessage, $e->getMessage());
			$this->assertEquals(Http::STATUS_CONFLICT, $e->getCode());
			throw $e;
		}
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

		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage("These users not exist in your Nextcoud instance : \n- user42\n");

		$this->spaceManager->addUsersInWorkspace($spaceId, $uids);
	}
}
