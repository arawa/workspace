/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2023 Andrei Zheksi <andrei.zheksi@arawa.fr>
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

import { expect } from '@jest/globals'
import { getAll, formatGroups, formatUsers, checkGroupfolderNameExist, enableAcl, addGroupToGroupfolder, addGroupToManageACLForGroupfolder, removeGroupToManageACLForGroupfolder, createGroupfolder, destroy, rename } from '../../services/groupfoldersService.js'
import axios from '@nextcloud/axios'

jest.mock('axios')
jest.mock('../../services/Notifications/NotificationError')
jest.mock('../../Errors/Groupfolders/CheckGroupfolderNameError')

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
	},
}
const badResponseValue = {
	data: {
		ocs: {
			meta: {
				status: 'error',
				statuscode: 400,
			},
		},
	},
}
describe('getAll function', () => {
	beforeEach(() => {
		axios.mockClear()
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
		expect(result).toEqual(responseValue.data.ocs.data)
	})
	it('throws error if resp status is not ok', async () => {
		axios.get.mockResolvedValue(badResponseValue)
		try {
			await getAll()
		} catch (err) {
			expect(err).toBeInstanceOf(Error)
		}
	})
})

describe('formatGroups function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	it('calls axios.post method', async () => {
		const spy = jest.spyOn(axios, 'post')
		axios.post.mockResolvedValue(responseValue)
		await formatGroups({})
		expect(spy).toHaveBeenCalled()
	})
	it('returns entire object received from axios.post', async () => {
		axios.post.mockResolvedValue(responseValue)
		const res = await formatGroups({})
		expect(res).toEqual(responseValue)
	})
})

describe('formatUsers function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	it('calls axios.post method', () => {
		axios.post.mockResolvedValue(responseValue)
		formatUsers({})
		expect(axios.post).toBeCalled()
	})
	it('returns entire object received from axios.post', async () => {
		axios.post.mockResolvedValue(responseValue)
		const res = await formatUsers({})
		expect(res).toEqual(responseValue)
	})
})

/**
 * @deprecated
 */
describe('checkGroupfolderNameExist function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	it('does call to axios.get method', () => {
		const getSpy = jest.spyOn(axios, 'get')
		axios.get.mockResolvedValue(responseValue)
		checkGroupfolderNameExist('foobar')
		expect(getSpy).toBeCalled()
	})
	it('should be return false if new workspace name does not exist', async () => {
		axios.get.mockResolvedValue(responseValue)
		const result = await checkGroupfolderNameExist('foobar')
		expect(result).toBe(false)
	})
})

/**
 * @deprecated
 */
describe('enableAcl function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	it('calls axios.post method', async () => {
		axios.post.mockResolvedValue({ status: 200, ...responseValue })
		const result = await enableAcl(1)
		expect(result).toEqual(responseValue.data.ocs.data)
	})
	it('throws error if resp.status is not 200', async () => {
		axios.post.mockResolvedValue({ status: 500, ...responseValue })
		const promise = enableAcl()
		await expect(promise).rejects.toThrow('Groupfolders\' API doesn\'t enable ACL. May be a problem with the connection ?')
	})
})

/**
 * @deprecated
 */
describe('addGroupToGroupfolder', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	it('calls axios.post method', () => {
		const spy = jest.spyOn(axios, 'post')
		axios.post.mockResolvedValue(responseValue)
		addGroupToGroupfolder(1, 'SPACE-U-1')
		expect(spy).toBeCalled()
	})
	it('returns data value of the object received from axios.post call', async () => {
		axios.post.mockImplementation(() => Promise.resolve(responseValue))
		await expect(addGroupToGroupfolder(1, 'SPACE-U-1')).resolves.toEqual(responseValue.data.ocs.data)
	})
	it('throws a proper error message if request fails', async () => {
		axios.post.mockImplementation(() => Promise.reject(new Error()))
		await expect(addGroupToGroupfolder(1, 'SPACE-U-1')).rejects.toThrow('Error to add Space Manager group in the groupfolder')
	})
	it('calls proper API path and object as a parameter', async () => {
		axios.post.mockResolvedValue(responseValue)
		await addGroupToGroupfolder(1, 'SPACE-U-1')
		expect(axios.post).toHaveBeenCalledWith('/apps/groupfolders/folders/1/groups', { group: 'SPACE-U-1' })
	})
})

/**
 * @deprecated
 */
describe('addGroupToManageACLForGroupfolder', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	it('calls axios.post method', () => {
		const spy = jest.spyOn(axios, 'post')
		axios.post.mockResolvedValue(responseValue)
		addGroupToManageACLForGroupfolder(5, 'SPACE-U-5')
		expect(spy).toBeCalled()
	})
	it('calls groupfolders API with proper parameters', async () => {
		axios.post.mockResolvedValue(responseValue)
		await addGroupToManageACLForGroupfolder(5, 'SPACE-U-5')
		expect(axios.post).toHaveBeenCalledWith('/apps/groupfolders/folders/5/manageACL', {
			mappingType: 'group',
			mappingId: 'SPACE-U-5',
			manageAcl: true,
		})
	})
	it('returns data value of the object received from axios.post call', async () => {
		axios.post.mockImplementation(() => Promise.resolve(responseValue))
		await expect(addGroupToManageACLForGroupfolder(1, 'SPACE-U-1')).resolves.toEqual(responseValue.data.ocs.data)
	})
	it('throws proper error message if request fails', async () => {
		axios.post.mockImplementation(() => Promise.reject(new Error()))
		await expect(addGroupToManageACLForGroupfolder(1, 'SPACE-U-1')).rejects.toThrow('Error to add the Space Manager group in manage ACL groupfolder')
	})
})

describe('removeGroupToManageACLForGroupfolder', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	it('calls axios.post method', () => {
		const spy = jest.spyOn(axios, 'post')
		axios.post.mockResolvedValue(responseValue)
		removeGroupToManageACLForGroupfolder(5, 'SPACE-U-5')
		expect(spy).toBeCalled()
	})
	it('calls groupfolders API with proper parameters', async () => {
		axios.post.mockResolvedValue(responseValue)
		await removeGroupToManageACLForGroupfolder(5, 'SPACE-U-5')
		expect(axios.post).toHaveBeenCalledWith('/apps/groupfolders/folders/5/manageACL', {
			mappingType: 'group',
			mappingId: 'SPACE-U-5',
			manageAcl: false,
		})
	})
	it('returns data value of the object received from axios.post call', async () => {
		axios.post.mockImplementation(() => Promise.resolve(responseValue))
		await expect(removeGroupToManageACLForGroupfolder(1, 'SPACE-U-1')).resolves.toEqual(responseValue.data.ocs.data)
	})
	it('throws proper error message if request fails', async () => {
		axios.post.mockImplementation(() => Promise.reject(new Error()))
		await expect(removeGroupToManageACLForGroupfolder(1, 'SPACE-U-1')).rejects.toThrow('Impossible to remove the group from the advanced permissions.')
	})
})

/**
 * @deprecated
 */
describe('createGroupfolder', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	it('calls axios.post method with proper parameters', async () => {
		axios.post.mockResolvedValue(responseValue)
		await createGroupfolder('foobar')
		expect(axios.post).toHaveBeenCalledWith('/apps/groupfolders/folders', {
			mountpoint: 'foobar',
		})
	})
	it('throws error if response status is not 200', async () => {
		axios.post.mockResolvedValue(badResponseValue)
		const promise = createGroupfolder('foobar')
		await expect(promise).rejects.toThrow('Impossible to create a groupfolder')
	})
})

describe('destroy', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.delete method with proper parameters', async () => {
		const workspace = { id: 1, groupfolderId: 1 }
		const spaceId = workspace.id

		axios.delete.mockResolvedValue(
			{
				http: {
					statuscode: 200,
					message: 'The space is deleted.'
				},
				data: { 
					name: 'foobar',
					groups: ['SPACE-WM-1', 'SPACE-U-1'],
          space_id: 1,
          groupfolder_id: 1,
          state: 'delete'
				}
			}
		)
		axios.delete.mockResolvedValue({ status: 200, data: { ocs: { meta: { status: 'ok' } } } })

		await destroy(workspace)

		expect(axios.delete).toHaveBeenCalledWith(`/apps/workspace/spaces/${spaceId}`, {
			data: { workspace },
		})

		expect(axios.delete).toHaveBeenCalledWith(`/apps/groupfolders/folders/${workspace.groupfolderId}`)
	})
	it('calls axios.delete 2 times and returns resp.data value', async () => {
		const mockAxios = axios.delete.mockImplementationOnce(() => Promise.resolve({ status: 200, ...responseValue }))
			.mockImplementationOnce(() => Promise.resolve(responseValue))
		const result = await destroy('foobar')
		expect(result).toEqual(responseValue.data)
		expect(mockAxios).toHaveBeenCalledTimes(2)
	})
	it('returns result of first axios call if returned status is not 200', async () => {
		axios.delete.mockResolvedValue({ status: 500, ...responseValue })
		const result = await destroy('foobar')
		expect(result).toEqual(responseValue.data)
	})
})

describe('rename', () => {
	const workspace = {
		color: '#d3a967', groupfolderId: 1, groups: { 'SPACE-GE-1': { gid: 'SPACE-GE-1', displayName: 'GE-1' }, 'SPACE-U-1': { gid: 'SPACE-U-1', displayName: 'U-1' } }, id: 1, isOpen: false, name: 'Bonjour', quota: '20GB', users: { 'celine.pariel': { uid: 'celine.pariel', name: 'Céline Pariel', email: null, subtitle: null, groups: ['SPACE-U-1'], role: 'user' } },
	}

	beforeEach(() => {
		axios.mockClear()
	})
	it('the requests relative to the rename function should be called', async () => {
		const spyPatch = jest.spyOn(axios, 'patch')
		axios.patch.mockResolvedValue({
			data: {
				statuscode: 204,
				space: {},
			},
		})
		const spyPost = jest.spyOn(axios, 'post')
		axios.post.mockResolvedValue({})
		await rename(workspace, 'new')
		expect(spyPatch).toBeCalled()
		expect(spyPost).toBeCalled()
	})
	it('should return undefined if workspace name already exist', async () => {
		responseValue.data.ocs.data[1].mount_point = 'Bonjour'
		// axios.get.mockResolvedValue(responseValue)
		axios.patch.mockResolvedValue({})
		const result = await rename(workspace, 'Bonjour')
		expect(result).toEqual(undefined)
	})

})
