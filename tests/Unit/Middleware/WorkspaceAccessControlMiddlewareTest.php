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

namespace OCA\Workspace\Tests\Unit\Middleware;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCP\AppFramework\Controller;
use OCP\IGroupManager;
use OCP\IGroup;
use OCP\IUrlGenerator;
use OCP\IUser;
use OCP\IUserSession;

class WorkspaceAccessControlMiddlewareTest extends TestCase {
	
	/** @var Midddleware */
	private $middleware;

	/** @var IUser */
	private $user;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserSession */
	private $userSession;

	public function setUp(): void {

		// Sets up the user'session
		$this->userSession = $this->createMock(IUserSession::class);
		$this->user = $this->createTestUser('John Doe', 'John Doe', 'john@acme.org');
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($this->user);

		// Creates General Manager group 
		$this->groupManager = $this->createMock(IGroupManager::class);
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
		echo "\n";
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
	 * This test makes sure that the middleware allows general managers to use the app
	 */
	public function testGeneralManagerAllowed(): void {

		// Let's say user is in General manager group
		$this->groupManager->expects($this->once())
	       		->method('isInGroup')
			->with($this->user->getUID(), Application::GENERAL_MANAGER)
			->willReturn(true);

		// Instantiates our middleware
		$this->middleware = new WorkspaceAccessControlMiddleware(
			$this->groupManager,
			$this->createMock(IURLGenerator::class),
			$this->userSession);

		// Runs the beforeController method
		$result = $this->middleware->beforeController(
			$this->createMock(Controller::class),
			'dummy',
		);

		$this->assertEquals(null, $result);
	}

	/**
	 * This test makes sure that the middleware allows space managers to use the app
	 */
	public function testSpaceManagerAllowed() {

		// Let's say user is only in a space manager group
		$this->groupManager->method('isInGroup')
			->withConsecutive(
				[$this->user->getUID(), Application::GENERAL_MANAGER],
			        [$this->user->getUID(), 'GE-Test'],
			)
			->willReturnOnConsecutiveCalls(false, true);

		$groups = $this->createTestGroup('GE-Test', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
	       		->method('search')
			// TODO Use global constant instead of 'GE-'
			->with('GE-')
			->willReturn([$groups]);

		// Instantiates our middleware
		$this->middleware = new WorkspaceAccessControlMiddleware(
			$this->groupManager,
			$this->createMock(IURLGenerator::class),
			$this->userSession);

		// Runs the beforeController method
		$result = $this->middleware->beforeController(
			$this->createMock(Controller::class),
			'dummy',
		);

		$this->assertEquals(null, $result);
	}

	/**
	 * This test makes sure that the middleware allows regular users to use the app
	 */
	public function testRegularUserDenied(): void {

		// Let's say user is not in General manager group, nor in a Space manager group
		$this->groupManager->method('isInGroup')
			->withConsecutive(
				[$this->user->getUID(), Application::GENERAL_MANAGER],
			        [$this->user->getUID(), 'GE-Test'],
			)
			->willReturnOnConsecutiveCalls(false, false);

		$groups = $this->createTestGroup('GE-Test', 'GE-Test', [$this->user]);
		$this->groupManager->expects($this->once())
	       		->method('search')
			// TODO Use global constant instead of 'GE-'
			->with('GE-')
			->willReturn([$groups]);

		// Instantiates our middleware
		$this->middleware = new WorkspaceAccessControlMiddleware(
			$this->groupManager,
			$this->createMock(IURLGenerator::class),
			$this->userSession);

		// Runs the beforeController method
		$this->expectException(AccessDeniedException::class);
		$this->middleware->beforeController(
			$this->createMock(Controller::class),
			'dummy',
		);

	}
}

