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

import { getAll } from '../../services/groupfoldersService.js'
import axios from '@nextcloud/axios'

jest.mock('axios')

describe('getAll function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
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
	it('calls axios.get method', () => {
		const getSpy = jest.spyOn(axios, 'get')
		getAll()
		expect(getSpy).toBeCalled()
	})
	it('returns data value of the object if resp status is ok', async () => {
		axios.get.mockResolvedValue(responseValue)
		let result = await getAll()
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
