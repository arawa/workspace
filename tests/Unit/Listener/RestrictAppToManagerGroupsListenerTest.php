<?php

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

declare(strict_types=1);

namespace OCA\Workspace\Tests\Unit\Listener;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Listener\RestrictAppToManagerGroupsListener;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCP\App\Events\AppEnableEvent;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RestrictAppToManagerGroupsListenerTest extends TestCase {
	private MockObject&IAppConfig $appConfig;
	private MockObject&IAppManager $appManager;
	private MockObject&IGroupManager $groupManager;
	private MockObject&IUserSession $userSession;
	private RestrictAppToManagerGroupsListener $listener;

	public function setUp(): void {
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->listener = new RestrictAppToManagerGroupsListener($this->appConfig, $this->appManager, $this->groupManager, $this->userSession);
	}

	public function testRestrictsToManagerGroupsAndAddsAdminWhenPending(): void {
		$admin = $this->createMock(IUser::class);
		$generalManager = $this->createMock(IGroup::class);
		$workspacesManagers = $this->createMock(IGroup::class);
		$this->appConfig->method('getAppValueBool')->willReturn(true);
		$this->userSession->method('getUser')->willReturn($admin);
		$this->groupManager->method('get')->willReturnMap([
			[ManagersWorkspace::GENERAL_MANAGER, $generalManager],
			[ManagersWorkspace::WORKSPACES_MANAGERS, $workspacesManagers],
		]);

		$this->appConfig->expects($this->once())->method('deleteAppValue')
			->with(RestrictAppToManagerGroupsListener::PENDING_KEY);
		$generalManager->expects($this->once())->method('addUser')->with($admin);
		$workspacesManagers->expects($this->never())->method('addUser');
		$this->appManager->expects($this->once())->method('enableAppForGroups')
			->with(Application::APP_ID, [$generalManager, $workspacesManagers]);

		$this->listener->handle(new AppEnableEvent(Application::APP_ID));
	}

	public function testRestrictsWithoutAddingUserFromOcc(): void {
		$generalManager = $this->createMock(IGroup::class);
		$this->appConfig->method('getAppValueBool')->willReturn(true);
		$this->userSession->method('getUser')->willReturn(null);
		$this->groupManager->method('get')->willReturnMap([
			[ManagersWorkspace::GENERAL_MANAGER, $generalManager],
			[ManagersWorkspace::WORKSPACES_MANAGERS, $this->createMock(IGroup::class)],
		]);

		$generalManager->expects($this->never())->method('addUser');
		$this->appManager->expects($this->once())->method('enableAppForGroups');

		$this->listener->handle(new AppEnableEvent(Application::APP_ID));
	}

	public function testDoesNotRestrictWhenAManagerGroupIsMissing(): void {
		$this->appConfig->method('getAppValueBool')->willReturn(true);
		$this->groupManager->method('get')->willReturnMap([
			[ManagersWorkspace::GENERAL_MANAGER, $this->createMock(IGroup::class)],
			[ManagersWorkspace::WORKSPACES_MANAGERS, null],
		]);

		$this->appManager->expects($this->never())->method('enableAppForGroups');

		$this->listener->handle(new AppEnableEvent(Application::APP_ID));
	}

	public function testKeepsGroupsChosenByAdmin(): void {
		$this->appConfig->method('getAppValueBool')->willReturn(true);

		$this->appConfig->expects($this->once())->method('deleteAppValue');
		$this->appManager->expects($this->never())->method('enableAppForGroups');

		$this->listener->handle(new AppEnableEvent(Application::APP_ID, ['admin']));
	}

	public function testDoesNothingWhenNotPending(): void {
		$this->appConfig->method('getAppValueBool')->willReturn(false);

		$this->appConfig->expects($this->never())->method('deleteAppValue');
		$this->appManager->expects($this->never())->method('enableAppForGroups');

		$this->listener->handle(new AppEnableEvent(Application::APP_ID));
	}

	public function testIgnoresOtherApps(): void {
		$this->appConfig->expects($this->never())->method('getAppValueBool');
		$this->appManager->expects($this->never())->method('enableAppForGroups');

		$this->listener->handle(new AppEnableEvent('files'));
	}
}
