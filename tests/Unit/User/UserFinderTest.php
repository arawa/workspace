<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2026 Arawa
 *
 * @author 2026 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Tests\Unit\User;

use OCA\Workspace\Exceptions\Notifications\EmailDoesntUniqueException;
use OCA\Workspace\User\UserFinder;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UserFinderTest extends TestCase {

	private MockObject&IUserManager $userManager;
	private UserFinder $userFinder;

	protected function setUp(): void {
		$this->userManager = $this->createMock(IUserManager::class);
		$this->userFinder = new UserFinder(
			$this->userManager,
			$this->createMock(IL10N::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testKnownUserIsFoundWithoutSearch(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('getByEmail')->willReturn([]);
		$this->userManager->method('get')->with('alice')->willReturn($user);
		$this->userManager->expects($this->never())->method('searchDisplayName');

		$this->assertSame($user, $this->userFinder->findUser('alice'));
	}

	public function testUnmappedUserIsFoundAfterSearch(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')->willReturnOnConsecutiveCalls(null, $user);
		$this->userManager->method('getByEmail')->willReturn([]);
		$this->userManager->expects($this->once())->method('searchDisplayName')->with('ldapuser', 10);

		$this->assertSame($user, $this->userFinder->findUser('ldapuser'));
	}

	public function testUidContainingAtIsFoundByUid(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('getByEmail')->willReturn([]);
		$this->userManager->method('get')->with('jdoe@corp')->willReturn($user);

		$this->assertSame($user, $this->userFinder->findUser('jdoe@corp'));
	}

	public function testShortEmailIsFoundByEmail(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')->willReturn(null);
		$this->userManager->method('getByEmail')->with('a@example.com')->willReturn([$user]);

		$this->assertSame($user, $this->userFinder->findUser('a@example.com'));
	}

	public function testUnmappedEmailIsFoundAfterSearch(): void {
		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')->willReturn(null);
		$this->userManager->method('getByEmail')->willReturnOnConsecutiveCalls([], [$user]);
		$this->userManager->expects($this->once())->method('searchDisplayName');

		$this->assertSame($user, $this->userFinder->findUser('ldap@example.com'));
	}

	public function testUnknownUserReturnsNull(): void {
		$this->userManager->method('getByEmail')->willReturn([]);

		$this->assertNull($this->userFinder->findUser('nobody@example.com'));
	}

	public function testEmptyPatternIsNotSearched(): void {
		$this->userManager->method('getByEmail')->willReturn([]);
		$this->userManager->expects($this->never())->method('searchDisplayName');

		$this->assertNull($this->userFinder->findUser(''));
	}

	public function testSharedEmailThrows(): void {
		// Since Nextcloud 31, get() resolves an email to its first user.
		$this->userManager->method('get')->willReturn($this->createMock(IUser::class));
		$this->userManager->method('getByEmail')->willReturn([
			$this->createMock(IUser::class),
			$this->createMock(IUser::class),
		]);

		$this->expectException(EmailDoesntUniqueException::class);
		$this->userFinder->findUser('shared@example.com');
	}
}
