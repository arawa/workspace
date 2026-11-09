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

namespace OCA\Workspace\Listener;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\Group\ManagersWorkspace;
use OCP\App\Events\AppEnableEvent;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IGroupManager;
use OCP\IUserSession;

/**
 * Limits the app to the manager groups the first time it is enabled after install.
 *
 * @template-implements IEventListener<AppEnableEvent>
 */
class RestrictAppToManagerGroupsListener implements IEventListener {
	public const PENDING_KEY = 'restrict_to_groups_pending';

	public function __construct(
		private IAppConfig $appConfig,
		private IAppManager $appManager,
		private IGroupManager $groupManager,
		private IUserSession $userSession,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof AppEnableEvent) || $event->getAppId() !== Application::APP_ID) {
			return;
		}

		if (!$this->appConfig->getAppValueBool(self::PENDING_KEY, lazy: true)) {
			return;
		}

		// Cleared on any enable, so a choice made by the admin is never overwritten later.
		// shortcut: installs that never fire AppEnableEvent (shipped app, app:install --keep-disabled) keep the flag armed until the next enable, upgrade if that path matters.
		$this->appConfig->deleteAppValue(self::PENDING_KEY);

		if ($event->getGroupIds() !== []) {
			return;
		}

		$generalManager = $this->groupManager->get(ManagersWorkspace::GENERAL_MANAGER);
		$workspacesManagers = $this->groupManager->get(ManagersWorkspace::WORKSPACES_MANAGERS);

		// Restricting to missing groups would lock everybody out of the app
		if ($generalManager === null || $workspacesManagers === null) {
			return;
		}

		// The admin who enables the app must keep access to it (no user with occ)
		$user = $this->userSession->getUser();
		if ($user !== null) {
			$generalManager->addUser($user);
		}

		$this->appManager->enableAppForGroups(Application::APP_ID, [$generalManager, $workspacesManagers]);
	}
}
