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
	admin,
	createGeneralManagerWithSpace,
	editModal,
	openEditModal,
	saveEditModal,
	teamFolderRow,
} from '../support/helpers.js'

const GB = 1024 ** 3

// What a General Manager reads, in each language the tests run in.
// Sizes keep their English units in French, as the app writes them itself.
const labels = {
	en: {
		quota: 'Quota',
		quotaHelp: 'Set maximum workspace storage space',
		unlimited: 'unlimited',
		using: (quota) => `out of ${quota}`,
	},
	fr: {
		quota: 'Quota',
		quotaHelp: 'Définit l\'espace de stockage maximal pour l\'espace de travail',
		unlimited: 'illimité',
		using: (quota) => `sur ${quota}`,
	},
}

for (const [language, label] of Object.entries(labels)) {
	describe(`Workspace quota (${language})`, () => {
		let generalManager
		let space

		before(() => {
			createGeneralManagerWithSpace(language).then((created) => {
				({ generalManager, space } = created)
			})
		})

		const modal = () => editModal(language)
		// Its label is not translated
		const quotaSelect = () => modal().findByRole('combobox', { name: 'set quota' })

		// Opens the edit modal of the workspace and its quota select
		const openQuotaSelect = () => {
			openEditModal(generalManager, space, language)
			modal()
				.findByRole('heading', { name: label.quota })
				.should('be.visible')
			modal().should('contain', label.quotaHelp)
			quotaSelect().click()
		}

		// Saves the modal, then checks the quota on the workspace page, after a reload too since
		// the page shows it before the server answers, and on the Team folders admin page
		const saveAndCheck = (quota, bytes) => {
			saveEditModal(language)

			cy.get('.space-name .quota-bubble').should('have.text', quota)
			cy.reload()
			cy.get('.space-name .quota-bubble').should('have.text', quota)

			cy.login(admin)
			cy.visit('/settings/admin/groupfolders')
			teamFolderRow(space.name)
				.find('td.quota')
				.findByRole('combobox', { name: 'Quota' })
				.should('have.value', String(bytes))
		}

		it('lets a General Manager pick a quota from the list', () => {
			openQuotaSelect()

			// The proposed quotas, plus "unlimited"
			for (const option of ['1 GB', '5 GB', '10 GB', label.unlimited]) {
				cy.findByRole('option', { name: option }).should('be.visible')
			}
			cy.findByRole('option', { name: '5 GB' }).click()
			modal().should('contain', label.using('5 GB'))

			saveAndCheck('5 GB', 5 * GB)
		})

		it('lets a General Manager type a custom quota', () => {
			openQuotaSelect()

			// The select takes any "<number><unit>" typed in it
			quotaSelect().type('40Gb{enter}')
			modal().should('contain', label.using('40 GB'))

			saveAndCheck('40 GB', 40 * GB)
		})
	})
}
