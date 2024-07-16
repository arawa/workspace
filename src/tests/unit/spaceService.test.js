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

import { transferUsersToUserGroup } from '../../services/spaceService.js'
import axios from '@nextcloud/axios'

jest.mock('axios')

describe('transferUsersToUserGroup method', () => {
	beforeEach(() => {
		axios.mockClear()
	})

	const responseGroupfolder = {
		id: 1,
		mount_point: 'TeamA',
		groups: {
			dev: 31,
			sysadmin: 31,
		},
		quota: 5368709120,
		size: 0,
		acl: true,
		manage: [
			{
				type: 'group',
				id: 'dev',
				displayname: 'dev',
			},
			{
				type: 'user',
				id: 'user3',
				displayname: 'user3',
			},
			{
				type: 'user',
				id: 'user4',
				displayname: 'user4',
			},
		],
	}

	const responseMockedValue = {
		data: {
			groups: {
				'SPACE-GE1': {
					gid: 'SPACE-GE-1',
					displayName: 'GE-1',
				},
				'SPACE-U1': {
					gid: 'SPACE-U-1',
					displayName: 'U-1',
				},
				dev: {
					gid: 'dev',
					displayName: 'dev',
				},
				sysadmin: {
					gid: 'sysadmin',
					displayName: 'sysadmin',
				},
			},
			users: {
				alice: {
					uid: 'alice',
					name: 'alice',
					email: null,
					subtitle: null,
					groups: [
						'SPACE-GE-1',
						'SPACE-U-1',
						'dev',
					],
					role: 'wm',
				},
				bob: {
					uid: 'bob',
					name: 'bob',
					email: null,
					subtitle: null,
					groups: [
						'SPACE-GE-1',
						'SPACE-U-1',
						'dev',
					],
					role: 'wm',
				},
				jane: {
					uid: 'jane',
					name: 'jane',
					email: null,
					subtitle: null,
					groups: [
						'SPACE-GE-1',
						'SPACE-U-1',
						'dev',
					],
					role: 'wm',
				},
				user2: {
					uid: 'user2',
					name: 'user2',
					email: null,
					subtitle: null,
					groups: [
						'SPACE-U-1',
						'sysadmin',
					],
					role: 'user',
				},
				user3: {
					uid: 'user3',
					name: 'user3',
					email: null,
					subtitle: null,
					groups: [
						'SPACE-U-1',
						'sysadmin',
					],
					role: 'user',
				},
				user4: {
					uid: 'user4',
					name: 'user4',
					email: null,
					subtitle: null,
					groups: [
						'SPACE-U-1',
						'sysadmin',
					],
					role: 'user',
				},
			},
		},
	}

	it('Return object type', async () => {

		axios.post.mockResolvedValue(responseMockedValue)
		const result = await transferUsersToUserGroup('1', responseGroupfolder)

		expect(typeof (result)).toEqual('object')
	})

	it('Is not undefined', () => {
		axios.post.mockResolvedValue(responseMockedValue)

		const result = transferUsersToUserGroup('1', responseGroupfolder)

		expect(result).not.toBe(undefined)
	})
})
