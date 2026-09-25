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

namespace OCA\Workspace\Tests\Unit\Helper;

use OCA\GroupFolders\Folder\FolderManager;
use OCA\Workspace\Exceptions\GroupFolderFunctionException;
use OCA\Workspace\Helper\GroupfolderHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class GroupfolderHelperTest extends TestCase {

	private MockObject&ContainerInterface $appContainer;
	private MockObject&FolderManager $folderManager;

	private GroupfolderHelper $folderHelper;

	public function setUp(): void {
		parent::setUp();

		$this->appContainer = $this->createMock(ContainerInterface::class);
		$this->folderManager = $this->createMock(FolderManager::class);

		$this->appContainer
			->expects($this->once())
			->method('get')
			->with(FolderManager::class)
			->willReturn($this->folderManager)
		;

		$this->folderHelper = new GroupfolderHelper($this->appContainer);
	}

	public function testCreateFolderReturnsTheFolderId(): void {
		$this->folderManager
			->expects($this->once())
			->method('createFolder')
			->with('Espace01')
			->willReturn(42)
		;

		$actual = $this->folderHelper->createFolder('Espace01');

		$this->assertSame(42, $actual);
	}

	public function testCreateFolderThrowsGroupFolderFunctionExceptionWhenFolderManagerFails(): void {
		$this->folderManager
			->expects($this->once())
			->method('createFolder')
			->with('Espace01')
			->willThrowException(new \Exception('Database error. '))
		;

		$this->expectException(GroupFolderFunctionException::class);
		$this->expectExceptionMessage('Database error. Cannot use createFolder function from FolderManager.');

		$this->folderHelper->createFolder('Espace01');
	}
}
