<?php
/**
 * @copyright Copyright (c) 2022 Arawa
 *
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
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

namespace OCA\Workspace\AppInfo;

use OCA\Workspace\Middleware\IsGeneralManagerMiddleware;
use OCA\Workspace\Middleware\IsSpaceAdminMiddleware;
use OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware;
use OCA\Workspace\Service\SpaceService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\IRequest;
use OCP\IURLGenerator;

class Application extends App {
	public const APP_ID = 'workspace';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	public function register(IRegistrationContext $context): void {
		$context->registerService(WorkspaceAccessControlMiddleware::class, function ($c) {
			return new WorkspaceAccessControlMiddleware(
				$c->query(IURLGenerator::class),
				$c->query(UserService::class)
			);
		});

		$context->registerService(IsSpaceAdminMiddleware::class, function ($c) {
			return new IsSpaceAdminMiddleware(
				$c->query(IRequest::class),
				$c->query(UserService::class),
				$c->query(SpaceService::class)
			);
		});

		$context->registerService(IsGeneralManagerMiddleware::class, function ($c) {
			return new IsGeneralManagerMiddleware(
				$c->query(IRequest::class),
				$c->query(UserService::class)
			);
		});

		$context->registerMiddleware(WorkspaceAccessControlMiddleware::class);
		$context->registerMiddleware(IsSpaceAdminMiddleware::class);
	}
}
