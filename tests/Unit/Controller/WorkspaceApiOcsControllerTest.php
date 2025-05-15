<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2025 Baptiste Fotia <baptiste.fotia@arawa.fr>
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
use OCA\Workspace\Controller\WorkspaceApiOcsController;
use OCA\Workspace\Db\Space;
use OCA\Workspace\Db\SpaceMapper;
use OCA\Workspace\Folder\RootFolder;
use OCA\Workspace\Helper\GroupfolderHelper;
use OCA\Workspace\Service\Group\GroupFormatter;
use OCA\Workspace\Service\WorkspaceService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class WorkspaceApiOcsControllerTest extends TestCase {

	private IGroupManager&MockObject $groupManager;
	private GroupfolderHelper&MockObject $folderHelper;
	private IRequest&MockObject $request;
	private LoggerInterface&MockObject $logger;
	private RootFolder&MockObject $rootFolder;
	private SpaceMapper&MockObject $spaceMapper;
	private string $appName;
	private WorkspaceApiOcsController $controller;
	
	public function setUp(): void {
		$this->appName = 'workspace';
		$this->folderHelper = $this->createMock(GroupfolderHelper::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->request = $this->createMock(IRequest::class);
		$this->rootFolder = $this->createMock(RootFolder::class);
		$this->spaceMapper = $this->createMock(SpaceMapper::class);
		
		$this->controller = new WorkspaceApiOcsController(
			$this->request,
			$this->folderHelper,
			$this->groupManager,
			$this->logger,
			$this->rootFolder,
			$this->spaceMapper,
			$this->appName
		);
	}

	public function tearDown(): void {
		Mockery::close();
	}

	public function testFindGroupsFromWorkspaceReturnsValidDataResponse(): void {
		$spaceId = 1;

		/** @var Space&MockObject */
		$space = $this->createMock(Space::class);
				
		$this->spaceMapper
			->expects($this->once())
			->method('find')
			->with($spaceId)
			->willReturn($space)
		;

		$space
			->expects($this->once())
			->method('getGroupfolderId')
			->willReturn(1)
		;
	
		$this->rootFolder
			->expects($this->any())
			->method('getRootFolderStorageId')
			->willReturn(2)
		;

		$this->folderHelper
			->expects($this->once())
			->method('getFolder')
			->willReturn(
				[
					'id' => 1,
					'mount_point' => 'Espace01',
					'groups' => [
						'SPACE-GE-1' => [
							'displayName' => 'WM-Espace01',
							'permissions' => 31,
							'type' => 'group',
						],
						'SPACE-U-1' => [
							'displayName' => 'U-Espace01',
							'permissions' => 31,
							'type' => 'group',
						],
					],
					'quota' => -3,
					'size' => 0,
					'acl' => true,
					'manage' => [
						[
							'type' => 'group',
							'id' => 'SPACE-GE-1',
							'displayname' => 'WM-Espace01',
						],
					]
				]
			)
		;
		
		$groupUser = $this->createMock(IGroup::class);
		$groupWorkspaceManagerUser = $this->createMock(IGroup::class);

		$this->groupManager
			->expects($this->any())
			->method('get')
			->willReturn($groupUser, $groupWorkspaceManagerUser)
		;

		$groupUser
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-U-1')
		;
		$groupUser
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('U-Espace01')
		;
		$groupUser
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;
		$groupUser
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('count')
			->willReturn(0)
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getGID')
			->willReturn('SPACE-GE-1')
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getDisplayName')
			->willReturn('WM-Espace01')
		;
		$groupWorkspaceManagerUser
			->expects($this->any())
			->method('getBackendNames')
			->willReturn(['Database'])
		;

		$groupFormatter = Mockery::mock(GroupFormatter::class);
		$groupFormatter
			->shouldReceive('formatGroups')
			->with([$groupUser, $groupWorkspaceManagerUser])
			->andReturn([
				'SPACE-GE-1' => [
					'gid' => 'SPACE-GE-1',
					'displayName' => 'WM-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-GE-1'
				],
				'SPACE-U-1' => [
					'gid' => 'SPACE-U-1',
					'displayName' => 'U-Espace01',
					'types' => [
						'Database'
					],
					'usersCount' => 0,
					'slug' => 'SPACE-U-1'
				]
			])
		;
		
		$actual = $this->controller->findGroupsFromWorkspace($spaceId);

		$expected = new DataResponse(
			[
				"SPACE-GE-1" => [
					"gid" => "SPACE-GE-1",
					"displayName" => "WM-Espace01",
					"types" => [
					"Database"
					],
					"usersCount" => 0,
					"slug" => "SPACE-GE-1"
				],
				"SPACE-U-1" => [
					"gid" => "SPACE-U-1",
					"displayName" => "U-Espace01",
					"types" => [
					"Database"
					],
					"usersCount" => 0,
					"slug" => "SPACE-U-1"
				]
			],
			Http::STATUS_OK
		)
		;

		if (!($actual instanceof DataResponse) || !($expected instanceof DataResponse)) {
			return;
		}

		$this->assertEquals($expected, $actual);
		$this->assertEquals($expected->getData(), $actual->getData());
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
	}
}
