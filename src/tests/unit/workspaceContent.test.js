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

import { beforeEach, describe, expect, it, vi } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import { createSpace } from '../../services/spaceService.js'
import axios from '@nextcloud/axios'
import WorkspaceContent from '../../WorkspaceContent.vue'
import LeftSidebar from '../../LeftSidebar.vue'
import store from '../../store/index.js'

const wrappedHome = shallowMount(WorkspaceContent, {
	global: {
		plugins: [store],
		mocks: {
			$route: {
				params: { id: '1' },
				path: '/apps/workspace/',
			},
			$router: {
				push: vi.fn(),
			},
		},
		stubs: {
			'router-link': true,
			'router-view': true,
		},
	},
})

// const expect = require('chai').expect

describe('Home component tests', () => {

	it('WorkspaceContent.vue is mounted', () => {
		expect(wrappedHome.exists()).toBe(true)
	})

	it('ConvertQuotaForFrontend: Test regular quota', () => {
		const quota = store.getters.convertQuotaForFrontend('3221225472')
		expect(quota).toEqual('3 GB')
	})

	it('ConvertQuotaForFrontend: Test unlimited quota', () => {
		const quota = store.getters.convertQuotaForFrontend(-3)
		expect(quota).toEqual('unlimited')
	})

	it('Convert 10000MB to 10GB', () => {
		const quota = store.getters.convertQuotaForFrontend('10737418240')
		expect(quota).toEqual('10 GB')
	})

	it('Convert 23GB to 23GB', () => {
		const quota = store.getters.convertQuotaForFrontend('24696061952')
		expect(quota).toEqual('23 GB')
	})

	it('Convert -3 (int) to unlimited', () => {
		const quota = store.getters.convertQuotaForFrontend(-3)
		expect(quota).toEqual('unlimited')
	})

	it('Convert -3 (string) to unlimited', () => {
		const quota = store.getters.convertQuotaForFrontend('-3')
		expect(quota).toEqual('unlimited')
	})

	it('Return string type', () => {
		const quota = store.getters.convertQuotaForFrontend('10737418240')
		expect(typeof (quota)).toBe('string')
	})
})

describe('Creating spaces with different entries', () => {

	beforeEach(() => {
		axios.mockClear()
	})

	it('Home.methods.createSpace has been called', () => {
		const mockCreateSpace = vi.spyOn(LeftSidebar.methods, 'createSpace')

		mockCreateSpace('44_Sri_Lanka')

		expect(mockCreateSpace).toHaveBeenCalled()
	})

	it('Create the workspace "Sri_Lanka"', async () => {
		axios.post.mockResolvedValue({
			data: {
				name: 'Sri_Lanka',
				id_space: 421,
				folder_id: 809,
				color: '#b7752e',
				groups: {
					'SPACE-GE-421': {
						displayName: 'GE-421',
						gid: 'SPACE-GE-421',
					},
					'SPACE-U-421': {
						displayName: 'U-421',
						gid: 'SPACE-U-421',
					},
				},
				quota: -3,
				size: 0,
				acl: true,
				manage: [
					{
						type: 'group',
						id: 'SPACE-GE-421',
						displayname: 'WM-Sri_Lanka',
					},
				],
				statuscode: 201,
			},
		})

		const result = await createSpace('Sri_Lanka', 809)

		expect(result).not.toBe(undefined)
		expect(result.statuscode).toEqual(201)
	})
})

describe('Loading the workspace the URL asks for', () => {
	const space = { id: 42, name: 'Espace42' }

	// A component opened on a URL, with a store that holds the workspace or not
	const component = (params, storedSpace = undefined) => ({
		...WorkspaceContent.methods,
		$route: { params },
		$store: {
			getters: { getSpaceById: vi.fn(() => storedSpace) },
			commit: vi.fn(),
		},
	})

	beforeEach(() => {
		axios.get.mockClear()
	})

	it('Adds a workspace missing from the store to it', async () => {
		axios.get.mockResolvedValue({ data: { Espace42: space } })
		const vm = component({ space: '42' })

		await vm.loadRequestedSpace()

		expect(vm.$store.getters.getSpaceById).toHaveBeenCalledWith(42)
		expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('/apps/workspace/spaces/42'))
		expect(vm.$store.commit).toHaveBeenCalledWith('addSpaces', { spaces: { Espace42: space } })
	})

	it('Reads the id of the hub page too', async () => {
		axios.get.mockResolvedValue({ data: { Espace42: space } })
		const vm = component({ spaceId: '42' })

		await vm.loadRequestedSpace()

		expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('/apps/workspace/spaces/42'))
	})

	it('Leaves a workspace already in the store', async () => {
		const vm = component({ space: '42' }, space)

		await vm.loadRequestedSpace()

		expect(axios.get).not.toHaveBeenCalled()
		expect(vm.$store.commit).not.toHaveBeenCalled()
	})

	it('Looks up nothing on a page without a workspace, or with a name instead of an id', async () => {
		for (const params of [{}, { space: 'Espace42' }]) {
			await component(params).loadRequestedSpace()
		}

		expect(axios.get).not.toHaveBeenCalled()
	})

	it('Leaves the store as it is when the server does not give the workspace', async () => {
		axios.get.mockRejectedValue(new Error('Request failed with status code 404'))
		const vm = component({ space: '42' })
		vi.spyOn(console, 'error').mockImplementation(() => {})

		await vm.loadRequestedSpace()

		expect(vm.$store.commit).not.toHaveBeenCalled()
	})
})
