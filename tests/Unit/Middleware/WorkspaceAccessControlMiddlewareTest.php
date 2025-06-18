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

namespace OCA\Workspace\Tests\Unit\Middleware;

use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\IUrlGenerator;
use PHPUnit\Framework\TestCase;

class WorkspaceAccessControlMiddlewareTest extends TestCase {
	/**
	 * This test makes sure that the middleware allows general managers to use the app
	 */
	public function testGeneralManagerAllowed(): void {
		// Setup UserService so that isUserGeneralAdmin will return true
		$userService = $this->createMock(UserService::class);
		$userService->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(true);

		// Instantiates our middleware
		$middleware = new WorkspaceAccessControlMiddleware(
			$this->createMock(IURLGenerator::class),
			$userService);

		// Runs the beforeController method
		$result = $middleware->beforeController(
			$this->createMock(Controller::class),
			'dummy',
		);

		$this->assertEquals(null, $result);
	}

	/**
	 * This test makes sure that the middleware allows space managers to use the app
	 */
	public function testSpaceManagerAllowed(): void {
		// Setup UserService so that isUserGeneralAdmin will return false
		// but isSpaceManager will return true
		$userService = $this->createMock(UserService::class);
		$userService->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(false);
		$userService->expects($this->once())
			->method('isSpaceManager')
			->willReturn(true);

		// Instantiates our middleware
		$middleware = new WorkspaceAccessControlMiddleware(
			$this->createMock(IURLGenerator::class),
			$userService);

		// Runs the beforeController method
		$result = $middleware->beforeController(
			$this->createMock(Controller::class),
			'dummy',
		);

		$this->assertEquals(null, $result);
	}

	/**
	 * This test makes sure that the middleware allows regular users to use the app
	 */
	public function testRegularUserDenied(): void {
		// Setup UserService so that both isUserGeneralAdmin() and
		// isSpaceManager() will return false
		$userService = $this->createMock(UserService::class);
		$userService->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(false);
		$userService->expects($this->once())
			->method('isSpaceManager')
			->willReturn(false);

		// Instantiates our middleware
		$middleware = new WorkspaceAccessControlMiddleware(
			$this->createMock(IURLGenerator::class),
			$userService);

		// Runs the beforeController method
		$this->expectException(AccessDeniedException::class);
		$result = $middleware->beforeController(
			$this->createMock(Controller::class),
			'dummy',
		);
	}
}
