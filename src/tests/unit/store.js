import { curry, mapObjIndexed } from 'ramda'
import { getters } from '../../../src/store/getters'
import mutations from '../../../src/store/mutations'

const bindGetterToState = curry((getters, state, num, key) => getters[key](state, getters))
const expect = require('chai').expect

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
		expect(state.spaces['test-space']).to.not.be.undefined
	})

	it('Adds a group to the space', () => {
		mutations.addGroupToSpace(state, {
			name: 'test-space',
			group: 'test-group',
		})

		expect(state.spaces['test-space'].groups['test-group']).eql({
			gid: 'test-group',
			displayName: 'test-group',
		})
	})

	it('Removes a group to the space', () => {
		mutations.removeGroupFromSpace(state, {
			name: 'test-space',
			group: 'test-group',
		})

		/* https://github.com/standard/standard/issues/690#issuecomment-278533482 */
		/* eslint-disable-next-line no-unused-expressions */
		expect(state.spaces['test-space'].groups['test-group']).to.be.undefined
	})

	it('Sets space quota', () => {
		mutations.setSpaceQuota(state, {
			name: 'test-space',
			quota: '1TB',
		})

		expect(state.spaces['test-space'].quota).equal('1TB')
	})

	it('Get space quota', () => {
		const getters = bindGetters()
		const quota = getters.quota('test-space')
		expect(quota).equal('unlimited')
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

		return expect(state.spaces['test-space'].users['John Doe']).not.undefined
	})

	it('Count users in workspace', () => {
		const getters = bindGetters()
		const count = getters.spaceUserCount('test-space')
		expect(count).equal(1)
	})

	it('Count users in group', () => {
		const getters = bindGetters()
		const count = getters.groupUserCount('test-space', 'GE-test-space')
		expect(count).equal(1)
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
