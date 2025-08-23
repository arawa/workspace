<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
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

namespace OCA\Workspace\Tests\Unit\Service;

use OCA\Workspace\Db\GroupFoldersGroupsMapper;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCA\Workspace\Service\Group\UserGroup;
use OCA\Workspace\Service\UserService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserServiceTest extends TestCase {
	private MockObject&IGroupManager $groupManager;
	private MockObject&IUser $user;
	private MockObject&IUserSession $userSession;
	private MockObject&LoggerInterface $logger;
	private MockObject&ConnectedGroupsService $connectedGroupService;
	private MockObject&IURLGenerator $urlGenerator;
	private MockObject&UserGroup $userGroup;
	private MockObject&GroupFoldersGroupsMapper $groupfoldersGroupsMapper;

	private const GROUP_WKSUSER = 'WorkspacesManagers';

	public function setUp(): void {
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->connectedGroupService = $this->createMock(ConnectedGroupsService::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->userGroup = $this->createMock(UserGroup::class);
		$this->groupfoldersGroupsMapper = $this->createMock(GroupFoldersGroupsMapper::class);

		// Sets up the user'session
		$this->userSession = $this->createMock(IUserSession::class);
		$this->user = $this->createTestUser('John Doe', 'John Doe', 'john@acme.org');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
	}

	private function createTestUser($id, $name, $email): MockObject&IUser {
		$mockUser = $this->createMock(IUser::class);
		$mockUser->expects($this->any())
			->method('getUID')
			->will($this->returnValue($id));
		$mockUser->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue($name));
		$mockUser->expects($this->any())
			->method('getEMailAddress')
			->willReturn($email);
		return $mockUser;
	}

	private function createTestGroup($id, $name, $users): MockObject&IGroup {
		$mockGroup = $this->createMock(IGroup::class);
		$mockGroup->expects($this->any())
			->method('getGID')
			->will($this->returnValue($id));
		$mockGroup->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue($name));
		$mockGroup->expects($this->any())
			->method('getUsers')
			->willReturn($users);
		return $mockGroup;
	}

	public function testGeneralAdminCanAccessApp(): void {
		$userSession = $this->createMock(IUserSession::class);

		$user = $this->createTestUser('Bar Foo', 'Bar Foo', 'bar@acme.org');
		
		$userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		// Let's say user is in a space manager group
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($user->getUID(), 'GeneralManager')
			->willReturn(true);

	   $generalManagerGroup = $this->createTestGroup('GeneralManager', 'GeneralManager', [$user]);

	   $this->groupManager->expects($this->once())
			->method('search')
		   	->with('GE-')
		   	->willReturn([$generalManagerGroup]);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$userSession,
			$this->workspaceService);

		$result = $userService->canAccessApp();

		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}

	public function testRegularUsersCannotAccessApp(): void {
		$userSession = $this->createMock(IUserSession::class);
		$groupManager = $this->createMock(IGroupManager::class);

		$user = $this->createTestUser('Bar Foo', 'Bar Foo', 'bar@acme.org');
		
		$userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		// Let's say user is in a space manager group
		$groupManager->expects($this->any())
			->method('isInGroup')
			->with($user->getUID(), 'GeneralManager')
			->willReturn(false);

	   $GeneralManagerGroup = $this->createTestGroup('GeneralManager', 'GeneralManager', [$user]);

	   $groupManager->expects($this->once())
			->method('search')
		   	->with('GE-')
		   	->willReturn([$GeneralManagerGroup]);

		// Instantiates our service
		$userService = new UserService(
			$groupManager,
			$this->logger,
			$this->userManager,
			$userSession,
			$this->workspaceService);

		$result = $userService->canAccessApp();

		$this->assertIsBool($result);
		$this->assertEquals(false, $result);
	}

	public function testSpaceManagerCanAccessApp(): void {

		$userSession = $this->createMock(IUserSession::class);

		$user = $this->createTestUser('Bar Foo', 'Bar Foo', 'bar@acme.org');
		
		$userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);

		// Let's say user is in a space manager group
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($user->getUID(), 'SPACE-GE-1')
			->willReturn(true);

	   $groups = $this->createTestGroup('SPACE-GE-1', 'SPACE-GE-1', [$user]);

	   $this->groupManager->expects($this->once())
			->method('search')
		   	->with('GE-')
		   	->willReturn([$groups]);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$userSession,
			$this->workspaceService);

		$result = $userService->canAccessApp();
		
		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}
	
	/**
	 * @todo the removeGEFromWM should return a JSONResponse to test it
	 */
	public function testRemoveGEFromWM(): void {

		$groupManager = $this->createMock(IGroupManager::class);

		$user = $this->createTestUser('Foo Bar', 'Foo Bar', 'foo@acme.org');

		$groupU =  $this->createTestGroup('SPACE-U-1', 'U-1', [$user]);
		$groupGE =  $this->createTestGroup('SPACE-GE-1', 'GE-1', [$user]);
		$subgroupLanfeust =  $this->createTestGroup('Pouvoirs-1', 'Pouvoirs-1', [$user]);
		$WORKSPACES_MANAGER_GROUP = $this->createTestGroup($this::GROUP_WKSUSER, $this::GROUP_WKSUSER, [$user]);
		
		$groupManager->expects($this->any())
			->method('get')
			->with($this::GROUP_WKSUSER)
			->willReturn($WORKSPACES_MANAGER_GROUP);
		$WORKSPACES_MANAGER_GROUP->expects($this->any())
			->method('removeUser')
			->with($user);

		$groupManager->expects($this->once())
			->method('getUserGroups')
			->with($user)
			->willReturn([$groupGE, $groupU, $subgroupLanfeust, $WORKSPACES_MANAGER_GROUP]);

		$space = [
			'name' => 'Lanfeust',
			'color'	=> 'blue',
			'isOpen' => false,
			'groups' => [
				$groupGE->getGID() => 31,
				$groupU->getGID() => 31,
				$subgroupLanfeust->getGID() => 31
			],
			'id' => 1,
			'groupfolderId' => 300,
			'quota' => 'unlimited',
			'users' => [],
		];
		
		// Instantiates our service
		$userService = new UserService(
			$groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);

		$userService->removeGEFromWM($user, $space['id']);

	}

	public function testFormatUser(): void {

		$groupU =  $this->createTestGroup('SPACE-U-1', 'U-1', [$this->user]);
		$groupGE =  $this->createTestGroup('SPACE-GE-1', 'GE-1', [$this->user]);
		$subgroupLanfeust =  $this->createTestGroup('Pouvoirs-1', 'Pouvoirs-1', [$this->user]);

		$space = [
			'name' => 'Lanfeust',
			'color'	=> 'blue',
			'isOpen' => false,
			'groups' => [
				$groupGE->getGID() => 31,
				$groupU->getGID() => 31,
				$subgroupLanfeust->getGID() => 31
			],
			'id' => 1,
			'groupfolderId' => 300,
			'quota' => 'unlimited',
			'users' => [],
		];

		$this->groupManager->expects($this->once())
			->method('getUserGroups')
			->willReturn([
				$groupU,
				$subgroupLanfeust
			]);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);
		
		$result = $userService->formatUser($this->user, $space, 'user');

		// Test if it's an array
		$this->assertIsArray($result);

		// Check the keys
		$this->assertArrayHasKey('uid', $result);
		$this->assertArrayHasKey('name', $result);
		$this->assertArrayHasKey('email', $result);
		$this->assertArrayHasKey('subtitle', $result);
		$this->assertArrayHasKey('groups', $result);
		$this->assertArrayHasKey('role', $result);

		// Check the value type of keys
		$this->assertIsString($result['uid']);
		$this->assertIsString($result['name']);
		$this->assertIsString($result['email']);
		$this->assertIsString($result['subtitle']);
		$this->assertIsArray($result['groups']);
		$this->assertIsString($result['role']);

	}

	/**
	 * This test makes sure that the isUserGeneralAdmin() method return true
	 * when user is a general manager
	 */
	public function testIsUserGeneralAdmin(): void {
		// Let's say user is in General manager group
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($this->user->getUID(), ManagersWorkspace::GENERAL_MANAGER)
			->willReturn(true);

		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($this->user);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->logger,
			$this->connectedGroupService,
			$this->urlGenerator,
			$this->userGroup,
			$this->groupfoldersGroupsMapper);

		// Runs the method to be tested
		$result = $userService->isUserGeneralAdmin();

		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isUserGeneralAdmin() method return false
	 * when user is not a general manager
	 */
	public function testIsNotUserGeneralAdmin(): void {
		// Let's say user is in General manager group
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($this->user->getUID(), ManagersWorkspace::GENERAL_MANAGER)
			->willReturn(false);

		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($this->user);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->logger,
			$this->connectedGroupService,
			$this->urlGenerator,
			$this->userGroup,
			$this->groupfoldersGroupsMapper);
		// Runs the method to be tested
		$result = $userService->isUserGeneralAdmin();

		$this->assertIsBool($result);
		$this->assertEquals(false, $result);
	}

	/**
	 * This test makes sure that the isSpaceManager() method return true when user
	 * is a space manager
	 */
	public function testIsSpaceManager(): void {
		// Let's say user is in a space manager group
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($this->user->getUID(), 'SPACE-GE-Test')
			->willReturn(true);
		$groups = $this->createTestGroup('SPACE-GE-Test', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
			->method('search')
			// TODO Use global constant instead of 'GE-'
			->with('SPACE-GE-')
			->willReturn([$groups]);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->logger,
			$this->connectedGroupService,
			$this->urlGenerator,
			$this->userGroup,
			$this->groupfoldersGroupsMapper);

		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($this->user);

		// Runs the method to be tested
		$result = $userService->isSpaceManager();

		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isSpaceManager() method return false when user
	 * is not a space manager
	 */
	public function testIsNotSpaceManager(): void {
		// Let's say user is in a space manager group
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($this->user->getUID(), 'SPACE-GE-Test')
			->willReturn(true);
		$groups = $this->createTestGroup('SPACE-GE-Test', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
			->method('search')
			// TODO Use global constant instead of 'GE-'
			->with('SPACE-GE-')
			->willReturn([$groups]);

		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($this->user);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->logger,
			$this->connectedGroupService,
			$this->urlGenerator,
			$this->userGroup,
			$this->groupfoldersGroupsMapper);

		// Runs the method to be tested
		$result = $userService->isSpaceManager();

		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isSpaceManagerOfSpace() method return true when user
	 * is manager of a space
	 */
	public function testIsNotSpaceManagerOfSpace(): void {
		// Let's say user is manager of the space
		$group = $this->createTestGroup('SPACE-GE-1', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($this->user->getUID(), $group->getGID())
			->willReturn(true);

		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($this->user);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->logger,
			$this->connectedGroupService,
			$this->urlGenerator,
			$this->userGroup,
			$this->groupfoldersGroupsMapper);

		// Runs the method to be tested
		$result = $userService->isSpaceManagerOfSpace([
			'id' => 1,
			'groupfolder_id' => 32,
			'name' => 'Foo',
			'color_code' => '#ffffff'
		]);

		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isSpaceManagerOfSpace() method return false when user
	 * is not manager of a space
	 */
	public function testIsSpaceManagerOfSpace(): void {
		// Let's say user is not manager of the space
		$group = $this->createTestGroup('SPACE-GE-1', 'GE-Test', []);
		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($this->user->getUID(), $group->getGID())
			->willReturn(false);

		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($this->user);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->logger,
			$this->connectedGroupService,
			$this->urlGenerator,
			$this->userGroup,
			$this->groupfoldersGroupsMapper);

		// Runs the method to be tested
		$result = $userService->isSpaceManagerOfSpace([
			'id' => 1,
			'groupfolder_id' => 32,
			'name' => 'Foo',
			'color_code' => '#ffffff'
		]);

		$this->assertIsBool($result);
		$this->assertEquals(false, $result);
	}
}
