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
 *
 */

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'
import { flushPromises, shallowMount } from '@vue/test-utils'
import axios from '@nextcloud/axios'
import { getManagers } from '../../services/spaceService.js'
import actions from '../../store/actions.js'
import SpaceTable from '../../SpaceTable.vue'
import store from '../../store/index.js'

const space = (id) => ({
	id,
	name: `space-${id}`,
	color: '#f5f5f5',
	quota: -3,
	managers: null,
	users: {},
	groups: {},
})

describe('getManagers service', () => {
	beforeEach(() => {
		axios.get.mockReset()
	})

	it('asks for the managers of all the workspaces in one GET request', async () => {
		axios.get.mockResolvedValue({ data: {} })

		await getManagers([1, 2, 3])

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get.mock.calls[0][0]).toMatch(/\/apps\/workspace\/managers$/)
		expect(axios.get.mock.calls[0][1]).toEqual({ params: { workspaceIds: '1,2,3' } })
	})
})

describe('loadManagers action', () => {
	let context

	beforeEach(() => {
		axios.get.mockReset()
		context = {
			commit: vi.fn(),
			getters: {
				getSpaceById: (id) => [space(1), space(2)].find((s) => s.id === id),
			},
		}
	})

	it('stores the managers of each workspace', async () => {
		const alice = { uid: 'alice', name: 'Alice' }
		axios.get.mockResolvedValue({ data: { 1: [alice], 2: [] } })

		await actions.loadManagers(context, { workspaceIds: [1, 2] })

		expect(context.commit).toHaveBeenCalledWith('addSpaceAdminUsers', { name: 'space-1', managers: [alice] })
		expect(context.commit).toHaveBeenCalledWith('addSpaceAdminUsers', { name: 'space-2', managers: [] })
	})

	it('gives no manager to a workspace the server left out', async () => {
		axios.get.mockResolvedValue({ data: {} })

		await actions.loadManagers(context, { workspaceIds: [1] })

		expect(context.commit).toHaveBeenCalledWith('addSpaceAdminUsers', { name: 'space-1', managers: [] })
	})

	it('does not overwrite managers loaded in the meantime', async () => {
		const loaded = { ...space(1), managers: { alice: { uid: 'alice', name: 'Alice', email: 'a@acme.org' } } }
		context.getters.getSpaceById = () => loaded
		axios.get.mockResolvedValue({ data: { 1: [{ uid: 'bob', name: 'Bob' }] } })

		await actions.loadManagers(context, { workspaceIds: [1] })

		expect(context.commit).not.toHaveBeenCalled()
	})

	it('ignores a workspace which is no longer in the store', async () => {
		axios.get.mockResolvedValue({ data: { 99: [] } })

		await actions.loadManagers(context, { workspaceIds: [99] })

		expect(context.commit).not.toHaveBeenCalled()
	})

	it('does not throw when the request fails', async () => {
		axios.get.mockRejectedValue(new Error('Network Error'))

		await expect(actions.loadManagers(context, { workspaceIds: [1] })).resolves.toBeUndefined()
		expect(context.commit).not.toHaveBeenCalled()
	})
})

describe('SpaceTable loads the managers of the visible rows', () => {
	// Like vue-lazyload: tells the row is visible, and can do it again later
	const shows = []
	let autoShow = true
	const LazyComponent = {
		emits: ['show'],
		mounted() {
			shows.push(() => this.$emit('show'))
			if (autoShow) {
				this.$emit('show')
			}
		},
		render() {
			return h('div', this.$slots.default?.())
		},
	}

	const mountTable = () => shallowMount(SpaceTable, {
		global: {
			plugins: [store],
			mocks: {
				$Lazyload: { lazyLoadHandler: vi.fn() },
				$router: { push: vi.fn() },
			},
			stubs: {
				'lazy-component': LazyComponent,
				PageLoader: true,
				WorkspaceContentEmpty: true,
			},
			directives: {
				'element-visibility': {},
			},
		},
	})

	beforeEach(() => {
		vi.useFakeTimers()
		shows.length = 0
		autoShow = true
		axios.get.mockReset()
		axios.get.mockResolvedValue({ data: {} })
		store.commit('setSpaces', {
			spaces: {
				'space-1': space(1),
				'space-2': space(2),
				'space-3': space(3),
			},
		})
	})

	afterEach(() => {
		vi.useRealTimers()
	})

	it('sends one request for the rows which become visible together', async () => {
		const wrapper = mountTable()

		await vi.advanceTimersByTimeAsync(1000)

		const requests = axios.get.mock.calls.filter(([url]) => url.endsWith('/apps/workspace/managers'))
		expect(requests).toHaveLength(1)
		expect(requests[0][1]).toEqual({ params: { workspaceIds: '1,2,3' } })

		wrapper.unmount()
	})

	it('does not ask again for a row which shows again while its managers are loading', async () => {
		axios.get.mockReturnValue(new Promise(() => {}))
		const wrapper = mountTable()

		await vi.advanceTimersByTimeAsync(1000)
		shows.forEach((show) => show())
		await vi.advanceTimersByTimeAsync(1000)

		const requests = axios.get.mock.calls.filter(([url]) => url.endsWith('/apps/workspace/managers'))
		expect(requests).toHaveLength(1)

		wrapper.unmount()
	})

	it('does not ask for a row whose managers are already loaded', async () => {
		store.commit('addSpaceAdminUsers', { name: 'space-1', managers: [] })
		store.commit('addSpaceAdminUsers', { name: 'space-2', managers: [] })
		const wrapper = mountTable()

		await vi.advanceTimersByTimeAsync(1000)
		await flushPromises()

		const requests = axios.get.mock.calls.filter(([url]) => url.endsWith('/apps/workspace/managers'))
		expect(requests).toHaveLength(1)
		expect(requests[0][1]).toEqual({ params: { workspaceIds: '3' } })

		wrapper.unmount()
	})

	it('sends the request 250 ms after the first row shows, even if others keep showing', async () => {
		autoShow = false
		const wrapper = mountTable()

		wrapper.vm.initAdmins(1)
		await vi.advanceTimersByTimeAsync(200)
		wrapper.vm.initAdmins(2)
		await vi.advanceTimersByTimeAsync(49)
		expect(axios.get).not.toHaveBeenCalled()
		await vi.advanceTimersByTimeAsync(1)

		expect(axios.get).toHaveBeenCalledTimes(1)
		expect(axios.get.mock.calls[0][1]).toEqual({ params: { workspaceIds: '1,2' } })

		wrapper.vm.initAdmins(3)
		await vi.advanceTimersByTimeAsync(250)
		expect(axios.get).toHaveBeenCalledTimes(2)
		expect(axios.get.mock.calls[1][1]).toEqual({ params: { workspaceIds: '3' } })

		wrapper.unmount()
	})

	it('does not send the pending request once the table is gone', async () => {
		autoShow = false
		const wrapper = mountTable()

		wrapper.vm.initAdmins(1)
		wrapper.unmount()
		await vi.advanceTimersByTimeAsync(1000)

		expect(axios.get).not.toHaveBeenCalled()
	})
})
