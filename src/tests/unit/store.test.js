/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
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
 *
 */

import { curry, mapObjIndexed } from 'ramda'
import { getters } from '../../store/getters.js'
import mutations from '../../store/mutations.js'

const bindGetterToState = curry((getters, state, num, key) => getters[key](state, getters))
// const expect = require('chai').expect

describe('Vuex store tests', () => {
	let state
	let bindGetters

	beforeEach(() => {
		state = {
			spaces: {
				'test-space': {
					color: '#f5f5f5',
					groups: [],
					id: '42',
					isOpen: false,
					name: 'test-space',
					quota: 'unlimited',
					users: [
						{
							uid: 1234,
							name: 'Jane Doe',
							email: 'jane@acme.org',
							subtitle: 'jane@acme.org',
							groups: [
								'GE-test-space',
								'yolo-1234',
							],
							role: 'admin',
						},
					],
				},
			},
		}
		bindGetters = () => mapObjIndexed(bindGetterToState(getters, state), getters)
	})

	it('Adds a space in the Vuex store', () => {
		mutations.addSpace(state, {
			color: '#f5f5f5',
			groups: [],
			id: '42',
			isOpen: false,
			name: 'test-space-2',
			quota: 'unlimited',
			users: [],
		})

		/* https://github.com/standard/standard/issues/690#issuecomment-278533482 */
		/* eslint-disable-next-line no-unused-expressions */
		expect(state.spaces['test-space']).not.toBe(undefined)
	})

	it('Adds a group to the space', () => {
		mutations.addGroupToSpace(state, {
			name: 'test-space',
			gid: 'test-group',
      slug: 'test-group',
      usersCount: 0,
		})

		expect(state.spaces['test-space'].groups['test-group']).toEqual({
			gid: 'test-group',
			displayName: 'test-group',
      slug: 'test-group',
      usersCount: 0,
		})
	})

	it('Removes a group to the space', () => {
		mutations.removeGroupFromSpace(state, {
			name: 'test-space',
			group: 'test-group',
		})

		/* https://github.com/standard/standard/issues/690#issuecomment-278533482 */
		/* eslint-disable-next-line no-unused-expressions */
		expect(state.spaces['test-space'].groups['test-group']).toBe(undefined)
	})

	it('Sets space quota', () => {
		mutations.setSpaceQuota(state, {
			name: 'test-space',
			quota: '1TB',
		})

		expect(state.spaces['test-space'].quota).toEqual('1TB')
	})

	it('Get space quota', () => {
		const getters = bindGetters()
		const quota = getters.quota('test-space')
		expect(quota).toEqual('unlimited')
	})

	it('Adds a user to the space', () => {
		mutations.addUserToWorkspace(state, {
			name: 'test-space',
			user: {
				uid: 123,
				name: 'John Doe',
				email: 'john@acme.org',
				subtitle: 'john@acme.org',
				groups: [],
				role: 'user',
			},
		})

		return expect(state.spaces['test-space'].users['123']).not.undefined
	})

	it('Count users in workspace', () => {
		const getters = bindGetters()
		const count = getters.spaceUserCount('test-space')
		expect(count).toEqual(1)
	})

	it('Count users in group', () => {
		const getters = bindGetters()
		const count = getters.groupUserCount('test-space', 'GE-test-space')
		expect(count).toEqual(1)
	})

	it('Removes a user from the space', () => {
		mutations.removeUserFromWorkspace(state, {
			name: 'test-space',
			user: {
				uid: 123,
				name: 'John Doe',
				email: 'john@acme.org',
				subtitle: 'john@acme.org',
				groups: [],
				role: 'user',
			},
		})

		return expect(state.spaces['test-space'].users['John Doe']).undefined
	})
})
