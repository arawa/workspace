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
 */

namespace OCA\Workspace\Service\Hub;

use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;

/**
 * Reads the icons of the apps shown on the Hub from the app's own folder,
 * without any HTTP request.
 */
class AppIconService {
	/**
	 * Apps whose icon the Hub shows. To show a new one, add it here.
	 */
	private const ALLOWED_APPS = [
		'groupfolders',
	];

	public function __construct(
		private IAppManager $appManager,
	) {
	}

	/**
	 * Returns the SVG contents, or null if the app is not allowed or not installed.
	 *
	 * @return array{icon_path: ?string, dark_icon_path: ?string}|null
	 */
	public function getIcons(string $appId): ?array {
		if (!in_array($appId, self::ALLOWED_APPS, true)) {
			return null;
		}

		try {
			$imgPath = $this->appManager->getAppPath($appId) . '/img/';
		} catch (AppPathNotFoundException) {
			return null;
		}

		return [
			'icon_path' => $this->readFirst($imgPath, [$appId . '.svg', 'app.svg']),
			'dark_icon_path' => $this->readFirst($imgPath, [$appId . '-dark.svg', 'app-dark.svg']),
		];
	}

	/**
	 * Same file names, in the same order, as IAppManager::getAppIcon().
	 */
	private function readFirst(string $imgPath, array $fileNames): ?string {
		foreach ($fileNames as $fileName) {
			if (is_file($imgPath . $fileName)) {
				return file_get_contents($imgPath . $fileName);
			}
		}

		return null;
	}
}
