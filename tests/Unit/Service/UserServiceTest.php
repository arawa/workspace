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

use OCA\Workspace\Group\Workspace\WorkspaceGroupsInfo;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\WorkspaceService;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserServiceTest extends TestCase {
	private IGroupManager $groupManager;
	private IUser $user;
	private IUserSession $userSession;
	private LoggerInterface $logger;
	private WorkspaceGroupsInfo $groupInfo;
	private WorkspaceService $workspaceService;

	public function setUp(): void {
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->workspaceService = $this->createMock(WorkspaceService::class);
		$this->groupInfo = $this->createMock(WorkspaceGroupsInfo::class);

		// Sets up the user'session
		$this->userSession = $this->createMock(IUserSession::class);
		$this->user = $this->createTestUser('John Doe', 'John Doe', 'john@acme.org');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($this->user);
	}

	private function createTestUser($id, $name, $email): IUser {
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

	private function createTestGroup($id, $name, $users): IGroup {
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
		$this->groupInfo->expects($this->once())
			->method('getGeneralManagerGroup')
			->willReturn('GeneralManager');

		$this->groupManager->expects($this->once())
			->method('isInGroup')
			->with($this->user->getUID(), 'GeneralManager')
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
			$this->groupInfo);

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
			->with($this->user->getUID(), 'GeneralManager')
			->willReturn(false);

		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($this->user);

		$this->groupInfo->expects($this->once())
			->method('getGeneralManagerGroup')
			->willReturn('GeneralManager');

		// Instantiates our service
		$userService = new UserService(
			$this->groupManager,
			$this->userSession,
			$this->logger,
			$this->groupInfo);

		// Runs the method to be tested
		$result = $userService->isUserGeneralAdmin();

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
			$this->groupInfo);

		$this->userSession->expects($this->once())
			->method('getUser')
			->with()
			->willReturn($this->user);

		// Runs the method to be tested
		$result = $userService->isSpaceManager();

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
			$this->groupInfo);

		// Runs the method to be tested
		$result = $userService->isSpaceManager();

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
			$this->groupInfo);

		// Runs the method to be tested
		$result = $userService->isSpaceManagerOfSpace([
			'id' => 1,
			'groupfolder_id' => 32,
			'name' => 'Foo',
			'color_code' => '#ffffff'
		]);

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
			$this->groupInfo);

		// Runs the method to be tested
		$result = $userService->isSpaceManagerOfSpace([
			'id' => 1,
			'groupfolder_id' => 32,
			'name' => 'Foo',
			'color_code' => '#ffffff'
		]);

		$this->assertEquals(false, $result);
	}
}
