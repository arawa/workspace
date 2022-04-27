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

import { convertGroupfolderToSpace } from '../../services/spaceService'
import axios from '@nextcloud/axios'

jest.mock('axios')

describe('convertGroupfolderToSpace method', () => {
	beforeEach(() => {
		axios.mockClear()
	})

	const responseGroupfolder = {
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
					id: 13,
					mount_point: 'Linagora',
					groups: {
						Ops5: 31,
						Dev5: 31,
					},
					quota: 10737418240,
					size: 0,
					acl: true,
				},
			},
		},
	}

	it('Return object type', async () => {

		axios.post.mockResolvedValue(
			{
				data:
				{
					space_name: 'Linagora',
					id_space: 1,
					folder_id: 13,
					color: '#fffff',
					groups: {
						'SPACE-GE1': 31,
						'SPACE-U1': 31,
						Ops5: 31,
						Dev5: 31,
					},
					users: [],
					statuscode: 201,
				},
			})
		const result = await convertGroupfolderToSpace('Linagora', responseGroupfolder)

		expect(typeof (result)).toEqual('object')
	})

	it('Is not undefined', async () => {
		axios.post.mockResolvedValue(
			{
				data:
				{
					space_name: 'Linagora',
					id_space: 1,
					folder_id: 13,
					color: '#fffff',
					groups: {
						'SPACE-GE1': 31,
						'SPACE-U1': 31,
						Ops5: 31,
						Dev5: 31,
					},
					users: [],
					statuscode: 201,
				},
			})

		const result = await convertGroupfolderToSpace('Linagora', responseGroupfolder)

		expect(result).not.toBe(undefined)
	})
})
