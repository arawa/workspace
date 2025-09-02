<?php

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

namespace OCA\Workspace\Exceptions;

abstract class AbstractNotification extends \Exception {
	/**
	 * @param string $title The title of the notification.
	 * @param string $message A description of the error notification.
	 * @param int $code The HTTP status code from OCP\AppFramework\Http.
	 * @param array $argsMessage An associative array containing additional variables.
	 *                           Example: [ 'spacename' => 'Space01' ]
	 */
	public function __construct(
		private string $title,
		string $message,
		int $code,
		private array $argsMessage = [],
	) {
		parent::__construct($message, $code);
	}

	public function getTitle(): string {
		return $this->title;
	}

	public function getArgsMessage(): array {
		return $this->argsMessage;
	}
}
