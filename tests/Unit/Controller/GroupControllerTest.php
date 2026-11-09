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

namespace OCA\Workspace\Tests\Unit\Controller;

use OCA\Workspace\Controller\GroupController;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Share\Group\GroupMembersOnlyChecker;
use OCA\Workspace\Share\Group\ShareMembersOnlyFilter;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\Collaboration\Collaborators\ISearch;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class GroupControllerTest extends TestCase {

	private MockObject&IGroupManager $groupManager;
	private MockObject&SpaceManager $spaceManager;
	private MockObject&IUserManager $userManager;
	private MockObject&ISearch $collaboratorSearch;
	private MockObject&LoggerInterface $logger;
	private MockObject&UserService $userService;
	private MockObject&GroupMembersOnlyChecker $groupMembersOnlyChecker;
	private MockObject&ShareMembersOnlyFilter $shareMembersOnlyFilter;

	private GroupController $controller;

	public function setUp(): void {
		parent::setUp();

		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->collaboratorSearch = $this->createMock(ISearch::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userService = $this->createMock(UserService::class);
		$this->groupMembersOnlyChecker = $this->createMock(GroupMembersOnlyChecker::class);
		$this->shareMembersOnlyFilter = $this->createMock(ShareMembersOnlyFilter::class);

		$this->controller = new GroupController(
			$this->groupManager,
			$this->spaceManager,
			$this->userManager,
			$this->collaboratorSearch,
			$this->logger,
			$this->userService,
			$this->groupMembersOnlyChecker,
			$this->shareMembersOnlyFilter,
		);
	}

	private function workspace(): array {
		return [
			'id' => 1,
			'name' => 'Espace01',
			'groups' => [
				'SPACE-GE-1' => [
					'gid' => 'SPACE-GE-1',
					'displayName' => 'WM-Espace01',
				],
				'SPACE-U-1' => [
					'gid' => 'SPACE-U-1',
					'displayName' => 'U-Espace01',
				],
				'SPACE-G-HR-1' => [
					'gid' => 'SPACE-G-HR-1',
					'displayName' => 'HR',
				],
			],
		];
	}

	private function mockUser(string $uid): IUser&MockObject {
		/** @var IUser&MockObject */
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->any())
			->method('getUID')
			->willReturn($uid)
		;

		$this->userManager
			->expects($this->once())
			->method('get')
			->with($uid)
			->willReturn($user)
		;

		return $user;
	}

	/**
	 * Returns the groups of the workspace and the WorkspacesManagers group, keyed by gid.
	 * The user is a member of the groups listed in $userGids.
	 * The groups listed in $missingGids do not exist.
	 *
	 * @return array<string, IGroup&MockObject>
	 */
	private function mockGroups(array $userGids, array $missingGids = []): array {
		$groups = [];
		$gids = [...array_keys($this->workspace()['groups']), 'WorkspacesManagers'];
		foreach (array_diff($gids, $missingGids) as $gid) {
			$group = $this->createMock(IGroup::class);
			$group
				->expects($this->any())
				->method('getGID')
				->willReturn($gid)
			;
			$groups[$gid] = $group;
		}

		$this->groupManager
			->expects($this->any())
			->method('isInGroup')
			->willReturnCallback(fn (string $uid, string $gid) => in_array($gid, $userGids, true))
		;

		$this->groupManager
			->expects($this->any())
			->method('groupExists')
			->willReturnCallback(fn (string $gid) => isset($groups[$gid]))
		;

		$this->groupManager
			->expects($this->any())
			->method('get')
			->willReturnCallback(fn (string $gid) => $groups[$gid] ?? null)
		;

		return $groups;
	}

	public function testRemoveUserFromWorkspaceRemovesUserFromItsWorkspaceGroups(): void {
		$user = $this->mockUser('user01');
		$groups = $this->mockGroups(['SPACE-U-1', 'SPACE-G-HR-1']);

		$groups['SPACE-U-1']
			->expects($this->once())
			->method('removeUser')
			->with($user)
		;

		$groups['SPACE-G-HR-1']
			->expects($this->once())
			->method('removeUser')
			->with($user)
		;

		$groups['SPACE-GE-1']
			->expects($this->never())
			->method('removeUser')
		;

		$this->userService
			->expects($this->once())
			->method('canRemoveWorkspaceManagers')
			->with($user)
			->willReturn(false)
		;

		$this->userService
			->expects($this->never())
			->method('removeGEFromWM')
		;

		$actual = $this->controller->removeUserFromWorkspace($this->workspace(), 'user01');

		$this->assertEquals(
			[
				'statuscode' => Http::STATUS_NO_CONTENT,
				'user' => 'user01',
				'groups' => [
					'SPACE-U-1',
					'SPACE-G-HR-1',
				],
			],
			$actual->getData()
		);
	}

	public function testRemoveUserFromWorkspaceWithSpaceAsJsonString(): void {
		$user = $this->mockUser('user01');
		$groups = $this->mockGroups(['SPACE-U-1']);

		$groups['SPACE-U-1']
			->expects($this->once())
			->method('removeUser')
			->with($user)
		;

		$this->userService
			->expects($this->once())
			->method('canRemoveWorkspaceManagers')
			->willReturn(false)
		;

		$actual = $this->controller->removeUserFromWorkspace(json_encode($this->workspace()), 'user01');

		$this->assertEquals(
			[
				'statuscode' => Http::STATUS_NO_CONTENT,
				'user' => 'user01',
				'groups' => [
					'SPACE-U-1',
				],
			],
			$actual->getData()
		);
	}

	public function testRemoveLastWorkspaceManagerFromWorkspaceAlsoRemovesItFromWorkspacesManagers(): void {
		$user = $this->mockUser('user01');
		$groups = $this->mockGroups(['SPACE-GE-1', 'SPACE-U-1']);

		$groups['SPACE-GE-1']
			->expects($this->once())
			->method('removeUser')
			->with($user)
		;

		$groups['SPACE-U-1']
			->expects($this->once())
			->method('removeUser')
			->with($user)
		;

		$this->userService
			->expects($this->once())
			->method('canRemoveWorkspaceManagers')
			->with($user)
			->willReturn(true)
		;

		$this->userService
			->expects($this->once())
			->method('removeGEFromWM')
			->with($user)
		;

		$actual = $this->controller->removeUserFromWorkspace($this->workspace(), 'user01');

		$this->assertEquals(
			[
				'statuscode' => Http::STATUS_NO_CONTENT,
				'user' => 'user01',
				'groups' => [
					'SPACE-GE-1',
					'SPACE-U-1',
					'WorkspacesManagers',
				],
			],
			$actual->getData()
		);
	}

	public function testRemoveUserFromWorkspaceThrowsExceptionWhenAGroupDoesNotExist(): void {
		$this->mockUser('user01');
		$groups = $this->mockGroups(['SPACE-U-1', 'SPACE-G-HR-1'], ['SPACE-G-HR-1']);

		$groups['SPACE-U-1']
			->expects($this->never())
			->method('removeUser')
		;

		$this->userService
			->expects($this->never())
			->method('removeGEFromWM')
		;

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('The SPACE-G-HR-1 group does not exist');

		$this->controller->removeUserFromWorkspace($this->workspace(), 'user01');
	}

	// The front end encodes a gid twice so that a "/" gets past Apache: the controller receives it
	// encoded once
	public function testAttachGroupToSpaceDecodesAGidWithASlash(): void {
		$this->spaceManager
			->method('get')
			->with(1)
			->willReturn(['groupfolderId' => 3, 'name' => 'Espace01'])
		;

		$this->spaceManager
			->expects($this->once())
			->method('attachGroup')
			->with(3, 'SPACE-G-R&D/Ops-1')
		;

		$this->controller->attachGroupToSpace(1, 'SPACE-G-R%26D%2FOps-1');
	}

	public function testDeleteKeepsThePlusSignsOfAGid(): void {
		$group = $this->createMock(IGroup::class);
		$group->expects($this->once())->method('delete');

		$this->groupManager
			->method('get')
			->with('SPACE-G-C++-1')
			->willReturn($group)
		;

		$actual = $this->controller->delete('SPACE-G-C%2B%2B-1', 1);

		$this->assertEquals(Http::STATUS_OK, $actual->getData()['status']);
	}
}
