/**
 * copyright Copyright (c) 2017 Arawa
 *
 * author 202é1 Baptiste Fotia <baptiste.fotia@arawa.fr>
 *
 * license GNU AGPL version 3 or any later version
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

import { showError } from '@nextcloud/dialogs'

/**
 *
 * @param {string} title error notification title
 * @param {string} text error notification text
 * @param {number | undefined} duration in milliseconds, 7 seconds by default, -1 for permanent notification
 */
export default function showNotificationError(title, text, duration = undefined) {
	title = t('workspace', title)
	text = t('workspace', text)
	const message = `<div style="max-width: 36rem;"><p style="font-weight: bold;display: block;">${title}</p><p>${text}</p></div>`
	const options = duration ? { isHTML: true, timeout: duration } : { isHTML: true }
	showError(message, options)
}
