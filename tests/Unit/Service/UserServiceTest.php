<?php

/**
 * @author Cyrille Bollu <cyrille@bollu.be>
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Workspace\Tests\Unit\Service;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\WorkspaceService;
use OCP\AppFramework\Controller;
use OCP\IGroupManager;
use OCP\IGroup;
use OCP\ILogger;
use OCP\IUrlGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;

class UserServiceTest extends TestCase {
	
	/** @var IUser */
	private $user;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ILogger */
	private $logger;

	/** @var UserManager */
	private $userManager;

	/** @var UserSession */
	private $userSession;

	/** @var WorkspaceService */
	private $workspaceService;

	public function setUp(): void {

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->workspaceService = $this->createMock(WorkspaceService::class);
		$this->userManager = $this->createMock(IUserManager::class);

		// Sets up the user'session
		$this->userSession = $this->createMock(IUserSession::class);
		$this->user = $this->createTestUser('John Doe', 'John Doe', 'john@acme.org');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

	}

	private function createTestUser($id, $name, $email) {
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

	private function createTestGroup($id, $name, $users) {
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
	public function TestRemoveGEFromWM(): void {

		$groupU =  $this->createTestGroup('SPACE-U-1', 'U-1', [$this->user]);
		$groupGE =  $this->createTestGroup('SPACE-GE-1', 'GE-1', [$this->user]);
		$subgroupLanfeust =  $this->createTestGroup('Pouvoirs-1', 'Pouvoirs-1', [$this->user]);

		$groupU->addUser($this->user);
		$groupGE->addUser($this->user);
		$subgroupLanfeust->addUser($this->user);


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
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);

		$result = $userService->removeGEFromWM($this->user, $space['id']);

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


		$groupU->addUser($this->user);
		$subgroupLanfeust->addUser($this->user);

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
			->with($this->user->getUID(), Application::GENERAL_MANAGER)
			->willReturn(true);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);

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
			->with($this->user->getUID(), Application::GENERAL_MANAGER)
			->willReturn(false);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isUserGeneralAdmin();

		$this->assertIsBool($result);
		$this->assertEquals(false, $result);
	}

	/**
	 * This test makes sure that the isSpaceManager() method return true when user 
	 * is a space manager
	 */
	public function testIsSpaceManager() {

		// Let's say user is in a space manager group
		$this->groupManager->expects($this->once())
		     	->method('isInGroup')
	     		->with($this->user->getUID(), 'GE-Test')
			->willReturn(true);
		$groups = $this->createTestGroup('GE-Test', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
	       		->method('search')
			// TODO Use global constant instead of 'GE-'
			->with('GE-')
			->willReturn([$groups]);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isSpaceManager();

		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isSpaceManager() method return false when user 
	 * is not a space manager
	 */
	public function testIsNotSpaceManager() {

		// Let's say user is in a space manager group
		$this->groupManager->expects($this->once())
		     	->method('isInGroup')
	     		->with($this->user->getUID(), 'GE-Test')
			->willReturn(true);
		$groups = $this->createTestGroup('GE-Test', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
	       		->method('search')
			// TODO Use global constant instead of 'GE-'
			->with('GE-')
			->willReturn([$groups]);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isSpaceManager();

		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isSpaceManagerOfSpace() method return true when user 
	 * is manager of a space
	 */
	public function testIsNotSpaceManagerOfSpace() {

		// Let's say user is manager of the space
		$group = $this->createTestGroup('SPACE-GE-1', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
		     	->method('isInGroup')
	     		->with($this->user->getUID(), $group->getGID())
			->willReturn(true);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isSpaceManagerOfSpace(1);

		$this->assertIsBool($result);
		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isSpaceManagerOfSpace() method return false when user 
	 * is not manager of a space
	 */
	public function testIsSpaceManagerOfSpace() {

		// Let's say user is not manager of the space
		$group = $this->createTestGroup('SPACE-GE-1', 'GE-Test', []);
		$this->groupManager->expects($this->once())
		     	->method('isInGroup')
	     		->with($this->user->getUID(), $group->getGID())
			->willReturn(false);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->logger,
			$this->userManager,
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isSpaceManagerOfSpace(1);

		$this->assertIsBool($result);
		$this->assertEquals(false, $result);
	}
}

