import mutations from '../../../src/store/mutations'

const expect = require('chai').expect

describe('Vuex store tests', () => {

	const state = {
		spaces: {},
	}

	it('Adds a space in the Vuex store', () => {
		mutations.addSpace(state, {
			color: '#f5f5f5',
			groups: [],
			id: '42',
			isOpen: false,
			name: 'test-space',
			quota: 'unlimited',
			users: [],
		})

		expect(state.spaces['test-space']).not.undefined
	})

	it('Adds a group to the space', () => {
		mutations.addGroupToSpace(state, {
			name: 'test-space',
			group: 'test-group',
		})

		expect(state.spaces['test-space'].groups['test-group']).equals('test-group')
	})

	it('Removes a group to the space', () => {
		mutations.removeGroupFromSpace(state, {
			name: 'test-space',
			group: 'test-group',
		})

		expect(state.spaces['test-space'].groups['test-group']).undefined
	})


	it('Sets space quota', () => {
		mutations.setSpaceQuota(state, {
			name: 'test-space',
			quota: '1TB',
		})

		expect(state.spaces['test-space'].quota).equal('1TB')
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

		expect(state.spaces['test-space'].users['John Doe']).not.undefined
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

		expect(state.spaces['test-space'].users['John Doe']).undefined
	})
})
