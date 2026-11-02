/**
 * @copyright Copyright (c) 2026 Arawa
 *
 * @license AGPL-3.0-or-later
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

import {
	createGeneralManagerWithSpace,
	editModal,
	navigationBullet,
	openEditModal,
	saveEditModal,
} from '../support/helpers.js'

// What a General Manager reads, in each language the tests run in. The color picker
// and its labels come from @nextcloud/vue
const labels = {
	en: {
		colorPicker: 'Color picker',
		choose: 'Choose',
		moreOptions: 'More options',
	},
	fr: {
		colorPicker: 'Sélecteur de couleurs',
		choose: 'Choisir',
		moreOptions: 'Plus d\'options',
	},
}

for (const [language, label] of Object.entries(labels)) {
	describe(`Workspace color (${language})`, () => {
		let generalManager
		let space

		before(() => {
			createGeneralManagerWithSpace(language).then((created) => {
				({ generalManager, space } = created)
			})
		})

		// Queried each time rather than aliased: the picker swaps its own content when
		// switching to "More options"
		const picker = () => cy.findByRole('dialog', { name: label.colorPicker })
		// The color dot of the modal, a button with no accessible name
		const colorDot = () => editModal(language).find('button.color-dot')

		// Opens the edit modal of the workspace, then the color picker from its color dot
		const openColorPicker = () => {
			openEditModal(generalManager, space, language)
			colorDot().click()
			picker().should('be.visible')
		}

		// Checks the workspace shows the color both in its page header and in the navigation
		const checkColor = (color) => {
			cy.get('.space-name .color-dot').should('have.css', 'background-color', color)
			navigationBullet(space.name).should('have.css', 'background-color', color)
		}

		// Confirms the color in the picker, saves the modal, then checks the color, after a
		// reload too since the page shows it before the server answers
		const chooseAndSave = (color) => {
			picker().findByRole('button', { name: label.choose }).click()
			picker().should('not.exist')
			colorDot().should('have.css', 'background-color', color)

			saveEditModal(language)
			checkColor(color)
			cy.reload()
			checkColor(color)
		}

		it('lets a General Manager pick a color of the palette', () => {
			openColorPicker()

			// "Feldspar" is rgb(201, 136, 121) in the palette of @nextcloud/vue, the same name in French
			picker().findByRole('radio', { name: 'Feldspar' }).click({ force: true })
			picker().findByRole('radio', { name: 'Feldspar' }).should('be.checked')

			chooseAndSave('rgb(201, 136, 121)')
		})

		it('lets a General Manager pick any color with "More options"', () => {
			openColorPicker()
			// Kept in a variable: an alias of invoke() is re-run, so it would yield the new color
			let previousColor
			colorDot().invoke('css', 'background-color').then((color) => {
				previousColor = color
			})

			picker().findByRole('button', { name: label.moreOptions }).click()

			// A fixed point of the saturation area of the advanced picker, so a failure can be replayed
			picker().find('.vc-saturation').then(($area) => {
				const { width, height } = $area[0].getBoundingClientRect()
				cy.wrap($area).click(width * 0.3, height * 0.7)
			})

			// The dot of the modal follows the picker, which yields the color to look for after saving
			colorDot()
				.should(($dot) => expect($dot.css('background-color')).not.to.equal(previousColor))
				.invoke('css', 'background-color')
				.then((color) => chooseAndSave(color))
		})
	})
}
