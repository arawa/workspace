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

const configDefault = {
	type: 'error',
	duration: 3000,
}

export default class NotificationError {

	// constructor(instanceVue) {
	constructor(title = 'Error', text, duration = null) {
		this.title = title
		this.text = text
		this.duration = duration
		// this.instanceVue = instanceVue
	}

	/**
	 *
	 * @param {object} config {
	 * title: string,
	 * text: string,
	 * duration: integer
	 * }
	 * @description in the config param, Only the title and text keys are required.
	 * @link please, read the official doc : https://www.npmjs.com/package/vue-notification#props
	 */
	push() {
		// this.instanceVue.$notify({
		// 	...configDefault,
		// 	...config,
		// })
		// const messageTitle = t('workspace', this.title)
		// const messageText = t('workspace', this.text)
		const message = `<div><p style="font-weight: bold;display: block;/*! width: 100%; */">${this.title}</p><p>${this.text}</p></div>`
		const options = this.duration ? { isHTML: true, timeout: this.duration } : { isHTML: true }
		showError(message, options)
	}

}
