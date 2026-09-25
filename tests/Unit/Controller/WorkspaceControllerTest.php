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

use OCA\Workspace\Controller\WorkspaceController;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Exceptions\GroupFolderFunctionException;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Formatter\WorkspaceFormatter;
use OCA\Workspace\Service\Group\ConnectedGroupsService;
use OCA\Workspace\Service\User\UserFormatter;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Space\SpaceManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkspaceControllerTest extends TestCase {

	private MockObject&IRequest $request;
	private MockObject&GroupfolderHelper $folderHelper;
	private MockObject&IGroupManager $groupManager;
	private MockObject&RootFolder $rootFolder;
	private MockObject&IUserManager $userManager;
	private MockObject&LoggerInterface $logger;
	private MockObject&IUserSession $userSession;
	private MockObject&SpaceMapper $spaceMapper;
	private MockObject&UserService $userService;
	private MockObject&WorkspaceFormatter $workspaceFormatter;
	private MockObject&WorkspaceService $workspaceService;
	private MockObject&UserFormatter $userFormatter;
	private MockObject&SpaceManager $spaceManager;
	private MockObject&IURLGenerator $urlGenerator;
	private MockObject&ConnectedGroupsService $connectedGroups;
	private MockObject&IAppConfig $appConfig;

	private WorkspaceController $controller;

	private const ROOT_STORAGE_ID = 2;

	public function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->folderHelper = $this->createMock(GroupfolderHelper::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->rootFolder = $this->createMock(RootFolder::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->spaceMapper = $this->createMock(SpaceMapper::class);
		$this->userService = $this->createMock(UserService::class);
		$this->workspaceFormatter = $this->createMock(WorkspaceFormatter::class);
		$this->workspaceService = $this->createMock(WorkspaceService::class);
		$this->userFormatter = $this->createMock(UserFormatter::class);
		$this->spaceManager = $this->createMock(SpaceManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->connectedGroups = $this->createMock(ConnectedGroupsService::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->rootFolder
			->expects($this->any())
			->method('getRootFolderStorageId')
			->willReturn(self::ROOT_STORAGE_ID)
		;

		$this->controller = new WorkspaceController(
			$this->request,
			$this->folderHelper,
			$this->groupManager,
			$this->rootFolder,
			$this->userManager,
			$this->logger,
			$this->userSession,
			$this->spaceMapper,
			$this->userService,
			$this->workspaceFormatter,
			$this->workspaceService,
			$this->userFormatter,
			$this->spaceManager,
			$this->urlGenerator,
			$this->connectedGroups,
			$this->appConfig,
			'workspace'
		);
	}

	private function workspace(int $id): array {
		return [
			'id' => $id,
			'groupfolder_id' => $id,
			'name' => "Espace0{$id}",
			'color_code' => '#46221f',
		];
	}

	private function groupfolder(int $id): array {
		return [
			'id' => $id,
			'mount_point' => "Espace0{$id}",
			'groups' => [
				"SPACE-GE-{$id}" => 31,
				"SPACE-U-{$id}" => 31,
			],
			'quota' => -3,
			'size' => 0,
			'acl' => true,
		];
	}

	/**
	 * Every workspace has its groupfolder, and the formatter returns
	 * the workspace name so the response content can be checked.
	 */
	private function mockExistingGroupfolders(): void {
		$this->folderHelper
			->expects($this->any())
			->method('getFolder')
			->willReturnCallback(function (int $folderId, int $rootStorageId) {
				$folderDefinition = $this->createMock('OCA\GroupFolders\Folder\FolderWithMappingsAndCache');
				$folderDefinition
					->expects($this->any())
					->method('toArray')
					->willReturn($this->groupfolder($folderId))
				;

				return $folderDefinition;
			})
		;

		$this->workspaceFormatter
			->expects($this->any())
			->method('format')
			->willReturnCallback(fn (array $workspace, array $folderInfo) => [
				'name' => $workspace['name'],
				'groupfolderId' => $folderInfo['id'],
			])
		;
	}

	private function mockCurrentUser(string $uid, array $gids): void {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->any())
			->method('getUID')
			->willReturn($uid)
		;

		$this->userSession
			->expects($this->any())
			->method('getUser')
			->willReturn($user)
		;

		$this->groupManager
			->expects($this->once())
			->method('getUserGroupIds')
			->with($user)
			->willReturn($gids)
		;
	}

	public function testFindAllAsGeneralManagerReturnsAllWorkspaces(): void {
		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(true)
		;

		$this->groupManager
			->expects($this->never())
			->method('getUserGroupIds')
		;

		$this->workspaceService
			->expects($this->once())
			->method('getAll')
			->with(0, 25, 'Espace')
			->willReturn([
				$this->workspace(1),
				$this->workspace(2),
			])
		;

		$this->mockExistingGroupfolders();

		$actual = $this->controller->findAll('Espace', 0, 25);

		$this->assertInstanceOf(JSONResponse::class, $actual);
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertEquals(
			[
				'Espace01' => [
					'name' => 'Espace01',
					'groupfolderId' => 1,
				],
				'Espace02' => [
					'name' => 'Espace02',
					'groupfolderId' => 2,
				],
			],
			$actual->getData()
		);
	}

	public function testFindAllAsUserReturnsOnlyWorkspacesOfItsUserGroups(): void {
		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(false)
		;

		$this->mockCurrentUser('user01', [
			'SPACE-U-1',
			'SPACE-GE-1',
			'SPACE-G-HR-1',
			'SPACE-U-3',
			'admin',
		]);

		$this->workspaceService
			->expects($this->once())
			->method('getAll')
			->with(
				null,
				null,
				null,
				'user01',
				$this->callback(fn (array $ids) => array_values($ids) === [1, 3])
			)
			->willReturn([
				$this->workspace(1),
				$this->workspace(3),
			])
		;

		$this->mockExistingGroupfolders();

		$actual = $this->controller->findAll();

		$this->assertEquals(
			[
				'Espace01' => [
					'name' => 'Espace01',
					'groupfolderId' => 1,
				],
				'Espace03' => [
					'name' => 'Espace03',
					'groupfolderId' => 3,
				],
			],
			$actual->getData()
		);
	}

	public function testFindAllAsUserWithoutUserGroupPassesNullSpaceIds(): void {
		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(false)
		;

		$this->mockCurrentUser('user01', ['admin']);

		$this->workspaceService
			->expects($this->once())
			->method('getAll')
			->with(null, null, null, 'user01', null)
			->willReturn([])
		;

		$this->folderHelper
			->expects($this->never())
			->method('getFolder')
		;

		$actual = $this->controller->findAll();

		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertEquals([], $actual->getData());
	}

	public function testFindAllSkipsWorkspaceWhoseGroupfolderDoesNotExist(): void {
		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(true)
		;

		$this->workspaceService
			->expects($this->once())
			->method('getAll')
			->willReturn([
				$this->workspace(1),
				$this->workspace(2),
			])
		;

		$folderDefinition = $this->createMock('OCA\GroupFolders\Folder\FolderWithMappingsAndCache');
		$folderDefinition
			->expects($this->once())
			->method('toArray')
			->willReturn($this->groupfolder(2))
		;

		$this->folderHelper
			->expects($this->exactly(2))
			->method('getFolder')
			->willReturnCallback(fn (int $folderId, int $rootStorageId) => $folderId === 1 ? null : $folderDefinition)
		;

		$this->logger
			->expects($this->once())
			->method('warning')
			->with('The groupfolder associated with Espace01 does not seem to exist.')
		;

		$this->workspaceFormatter
			->expects($this->once())
			->method('format')
			->with($this->workspace(2), $this->groupfolder(2))
			->willReturn(['name' => 'Espace02'])
		;

		$actual = $this->controller->findAll();

		$this->assertEquals(
			[
				'Espace02' => [
					'name' => 'Espace02',
				],
			],
			$actual->getData()
		);
	}

	public function testFindAllThrowsExceptionWhenGroupfolderFails(): void {
		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(true)
		;

		$this->workspaceService
			->expects($this->once())
			->method('getAll')
			->willReturn([
				$this->workspace(1),
			])
		;

		$this->folderHelper
			->expects($this->once())
			->method('getFolder')
			->with(1, self::ROOT_STORAGE_ID)
			->willThrowException(new GroupFolderFunctionException('Cannot use the getFolder function from FolderManager.'))
		;

		$this->workspaceFormatter
			->expects($this->never())
			->method('format')
		;

		$this->expectException(GroupFolderFunctionException::class);
		$this->expectExceptionMessage('Cannot use the getFolder function from FolderManager.');

		$this->controller->findAll();
	}

	public function testFindAllThrowsExceptionWhenGettingWorkspacesFails(): void {
		$this->userService
			->expects($this->once())
			->method('isUserGeneralAdmin')
			->willReturn(true)
		;

		$this->workspaceService
			->expects($this->once())
			->method('getAll')
			->willThrowException(new \Exception('Database error'))
		;

		$this->folderHelper
			->expects($this->never())
			->method('getFolder')
		;

		$this->expectException(\Exception::class);
		$this->expectExceptionMessage('Database error');

		$this->controller->findAll();
	}
}
