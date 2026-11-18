/**
 * @copyright Copyright (c) 2026 Arawa
 *
 * @license AGPL-3.0-or-later
 */

import { beforeEach, describe, expect, it, vi } from 'vitest'
import actions from '../../store/actions.js'
import mutations from '../../store/mutations.js'
import { removeWorkspace } from '../../services/spaceService.js'
import showNotificationError from '../../services/Notifications/NotificationError.js'

vi.mock('../../services/spaceService.js', () => ({
	getUsers: vi.fn(),
	addGroupToWorkspace: vi.fn(),
	removeWorkspace: vi.fn(),
}))
vi.mock('../../services/Notifications/NotificationError.js', () => ({ default: vi.fn() }))
vi.mock('../../router.js', () => ({ default: {} }))

describe('deleteWorkspace action', () => {
	const space = { id: 3, name: 'Space 03' }
	let context

	beforeEach(() => {
		vi.clearAllMocks()
		context = { dispatch: vi.fn() }
	})

	it('removes the workspace from the store and the counters when the server deletes it', async () => {
		removeWorkspace.mockResolvedValue({ http: { statuscode: 200 } })

		expect(await actions.deleteWorkspace(context, { space })).toBe(true)
		expect(context.dispatch.mock.calls.map(call => call[0])).toEqual([
			'removeSpace',
			'decrementCountWorkspaces',
			'decrementCountTotalWorkspaces',
			'decrementCountTotalWorkspacesByQuery',
		])
	})

	it('keeps the store and shows an error when the server does not delete it', async () => {
		removeWorkspace.mockResolvedValue(undefined)

		expect(await actions.deleteWorkspace(context, { space })).toBe(false)
		expect(context.dispatch).not.toHaveBeenCalled()
		expect(showNotificationError).toHaveBeenCalledOnce()
	})
})

describe('DECREMENT_COUNT_TOTAL_WORKSPACES_BY_QUERY mutation', () => {
	it('decrements the count but never goes below zero', () => {
		const state = { countTotalWorkspacesByQuery: 1 }

		mutations.DECREMENT_COUNT_TOTAL_WORKSPACES_BY_QUERY(state)
		mutations.DECREMENT_COUNT_TOTAL_WORKSPACES_BY_QUERY(state)

		expect(state.countTotalWorkspacesByQuery).toBe(0)
	})
})
