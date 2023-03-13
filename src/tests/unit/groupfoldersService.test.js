/**
 * @copyright Copyright (c) 2017 Arawa
*
* @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
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

import { getAll, get, formatGroups } from '../../services/groupfoldersService.js'
import axios from '@nextcloud/axios'
import NotificationError from '../../services/Notifications/NotificationError.js'

jest.mock('axios')
jest.mock('../../services/Notifications/NotificationError')

const responseValue = {
	data: {
		ocs: {
			meta: {
				status: 'ok',
				statuscode: 100,
				message: 'OK',
				totalitems: '',
				itemsperpage: '',
			},
			data: {
				1: {
					id: 1,
					mount_point: 'first',
					groups: {
						'SPACE-GE-1': 31,
						'SPACE-U-1': 31,
					},
					quota: -3,
					size: 0,
					acl: true,
					manage: [
						{
							type: 'group',
							id: 'SPACE-GE-1',
							displayname: 'GE-1',
						},
					],
				},
			},
		},
	}
}
const badResponseValue = {
	data: {
		ocs: {
			meta: {
				status: 'error'
			}
		}
	}
}
describe('getAll function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.get method', () => {
		const getSpy = jest.spyOn(axios, 'get')
		getAll()
		expect(getSpy).toBeCalled()
	})
	it('returns data value of the object if resp status is ok', async () => {
		axios.get.mockResolvedValue(responseValue)
		const result = await getAll()
		expect(Object.keys(result)).not.toContain('data')
	})
	it('throws error if resp status is not ok', async () => {
		axios.get.mockResolvedValue(badResponseValue)
		try {
			await getAll()
		} catch (err) {
			expect(err).toMatch('error')
		}
	})
})

describe('get function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.get method', () => {
		const getSpy = jest.spyOn(axios, 'get')
		try {
			get(1)
		} catch (err) {
			expect(getSpy).toBeCalled()
		}
	})
	it('returns data property of the object if response status is ok', async () => {
		axios.get.mockResolvedValue(responseValue)
		const res = await get(1)
		expect(res).toEqual(responseValue.data.ocs.data)
	})
	it('throws exception if response status is not ok', async () => {
		const spy = jest.spyOn(NotificationError.prototype, 'push')
		axios.get.mockResolvedValue(badResponseValue)
		try {
			await get(1)
		} catch (err) {
			expect(spy).toBeCalled()
			expect(err).toBeInstanceOf(Error)
		}
	})
})

describe('formatGroups function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.post method', async () => {
		const spy = jest.spyOn(axios, 'post')
		try {
			await formatGroups({})
		} catch (err) {
			expect(spy).toHaveBeenCalled()
		}
	})
	it('returns entire object received from axios.post', async () => {
		axios.post.mockResolvedValue({data: 'foobar'})
		let res = await formatGroups({})
		expect(res).toEqual({data: 'foobar'})
	})
})
