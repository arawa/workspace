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
			admins: [],
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

		expect(state.spaces['test-space'].groups['test-group']).equals('test-group')
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

	it('Set space quota', () => {
		mutations.setSpaceQuota(state, {
			name: 'test-space',
			quota: '1TB',
		})

		expect(state.spaces['test-space'].quota).equal('1TB')
	})

})
