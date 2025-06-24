<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Arawa
 *
 * @author 2024 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

namespace OCA\Workspace\Tests\Unit\Service\Workspace;

use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\Workspace\WorkspaceCheckService;
use PHPUnit\Framework\TestCase;

class WorkspaceCheckServiceTest extends TestCase {

	private \PHPUnit\Framework\MockObject\MockObject&SpaceService $spaceService;
	private WorkspaceCheckService $workspaceCheckService;

	public function setUp(): void {
		parent::setUp();

		$this->spaceService = $this->createMock(SpaceService::class);
		$this->workspaceCheckService = new WorkspaceCheckService($this->spaceService);
	}

	public function testCheckSpacenameWithoutSpecialCharacter(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithAtSymbol(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Esp@ce01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithPipe(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace|01');

		$this->assertTrue($result);
	}

	public function testCheckSpacenameWithTild(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace~01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithPercent(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Es%pace01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithSlash(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01/Group');

		$this->assertTrue($result);
	}

	public function testCheckSpacenameWithBackSlash(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01\Group');

		$this->assertTrue($result);
	}

	public function testCheckSpacenameWithAmpersand(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01&Group');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithEmbraces(): void {
		$embraceOpened = $this->workspaceCheckService->containSpecialChar('Espace01>Group');
		$embraceClosed = $this->workspaceCheckService->containSpecialChar('Espace01<Group');
		$embraces = $this->workspaceCheckService->containSpecialChar('Espace01<Group>');

		$this->assertTrue($embraceOpened);
		$this->assertTrue($embraceClosed);
		$this->assertTrue($embraces);
	}

	public function testCheckSpacenameWithSemiColon(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01;Group');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithComma(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01,Group');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithDot(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace.01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithColon(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01:Group');

		$this->assertTrue($result);
	}

	public function testCheckSpacenameWithExclamationMark(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01!');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithQuestionMark(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace01?');

		$this->assertTrue($result);
	}

	public function testCheckSpacenameWithApostrophe(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace\'01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithSharp(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace#01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithPlus(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace+01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithBrackets(): void {
		$bracketOpened = $this->workspaceCheckService->containSpecialChar('Espace(01');
		$bracketClosed = $this->workspaceCheckService->containSpecialChar('Espace01)');
		$brackets = $this->workspaceCheckService->containSpecialChar('Espace(01)');

		$this->assertFalse($bracketOpened);
		$this->assertFalse($bracketClosed);
		$this->assertFalse($brackets);
	}

	public function testCheckSpacenameWithCircumflexAccent(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace^01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithEqual(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace=01');

		$this->assertFalse($result);
	}

	public function testCheckSpacenameWithAsterisk(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace*');

		$this->assertTrue($result);
	}

	public function testCheckSpacenameWithSquareBrackets(): void {
		$squareBracketOpened = $this->workspaceCheckService->containSpecialChar('Espace[01');
		$squareBracketClosed = $this->workspaceCheckService->containSpecialChar('Espace01]');
		$squareBrackets = $this->workspaceCheckService->containSpecialChar('Espace[01]');

		$this->assertFalse($squareBracketOpened);
		$this->assertFalse($squareBracketClosed);
		$this->assertFalse($squareBrackets);
	}

	public function testCheckSpacenameWithLess(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace<');

		$this->assertTrue($result);
	}

	public function testCheckSpacenameWithGreater(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace>');

		$this->assertTrue($result);
	}

	public function testCheckSpacenameWithSpace(): void {
		$result = $this->workspaceCheckService->containSpecialChar('Espace 01');

		$this->assertFalse($result);
	}
}
