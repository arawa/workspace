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

import { beforeEach, describe, expect, it, vi } from 'vitest'
import axios from '@nextcloud/axios'
import actions from '../../store/actions.js'
import showNotificationError from '../../services/Notifications/NotificationError.js'

vi.mock('@nextcloud/router', () => ({ generateUrl: (url) => url }))
vi.mock('../../router.js', () => ({ default: {} }))
vi.mock('../../services/Notifications/NotificationError.js', () => ({ default: vi.fn() }))

describe('setSpaceQuota action', () => {
	let context

	beforeEach(() => {
		vi.clearAllMocks()
		context = {
			getters: { getSpaceByNameOrId: () => ({ id: 1, quota: 5368709120 }) },
			commit: vi.fn(),
		}
	})

	it('updates the quota in the store once the server accepts it', async () => {
		axios.post.mockResolvedValue({ status: 200 })

		await actions.setSpaceQuota(context, { name: 'Space01', quota: 10737418240 })

		expect(context.commit).toHaveBeenCalledWith('setSpaceQuota', { name: 'Space01', quota: 10737418240 })
		expect(showNotificationError).not.toHaveBeenCalled()
	})

	it('keeps the previous quota when the server answers with an error status', async () => {
		axios.post.mockResolvedValue({ status: 500, statusText: 'Server Error' })

		await actions.setSpaceQuota(context, { name: 'Space01', quota: 10737418240 })

		expect(context.commit).not.toHaveBeenCalled()
	})

	it('keeps the previous quota when the request fails', async () => {
		axios.post.mockRejectedValue(new Error('Network Error'))

		await actions.setSpaceQuota(context, { name: 'Space01', quota: 10737418240 })

		expect(context.commit).not.toHaveBeenCalled()
	})
})
