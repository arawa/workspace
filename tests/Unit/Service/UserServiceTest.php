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
use OCP\IUrlGenerator;
use OCP\IUser;
use OCP\IUserSession;

class UserServiceTest extends TestCase {
	
	/** @var IUser */
	private $user;

	/** @var IGroupManager */
	private $groupManager;

	/** @var UserSession */
	private $userSession;

	/** @var WorkspaceService */
	private $workspaceService;

	public function setUp(): void {

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->workspaceService = $this->createMock(WorkspaceService::class);

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
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isUserGeneralAdmin();

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
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isUserGeneralAdmin();

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
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isSpaceManager();

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
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isSpaceManager();

		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isSpaceManagerOfSpace() method return true when user 
	 * is manager of a space
	 */
	public function testIsNotSpaceManagerOfSpace() {

		$this->workspaceService->expects($this->once())
			->method('get')
			->willReturn(['space_name' => 'Test']);
		// Let's say user is manager of the space
		$group = $this->createTestGroup('GE-Test', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
		     	->method('search')
			->willReturn([$group]);
		$this->groupManager->expects($this->once())
		     	->method('isInGroup')
	     		->with($this->user->getUID(), $group->getGID())
			->willReturn(true);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isSpaceManagerOfSpace(1);

		$this->assertEquals(true, $result);
	}

	/**
	 * This test makes sure that the isSpaceManagerOfSpace() method return false when user 
	 * is not manager of a space
	 */
	public function testIsSpaceManagerOfSpace() {

		$this->workspaceService->expects($this->once())
			->method('get')
			->willReturn(['space_name' => 'Test']);
		// Let's say user is not manager of the space
		$group = $this->createTestGroup('GE-Test', 'GE-Test', []);
		$this->groupManager->expects($this->once())
		     	->method('search')
			->willReturn([$group]);
		$this->groupManager->expects($this->once())
		     	->method('isInGroup')
	     		->with($this->user->getUID(), $group->getGID())
			->willReturn(false);

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->workspaceService);

		// Runs the method to be tested
		$result = $userService->isSpaceManagerOfSpace(1);

		$this->assertEquals(false, $result);
	}
}

