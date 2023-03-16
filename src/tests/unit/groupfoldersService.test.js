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

import { getAll, get, formatGroups, formatUsers, checkGroupfolderNameExist, enableAcl, addGroupToGroupfolder, addGroupToManageACLForGroupfolder, removeGroupToManageACLForGroupfolder, createGroupfolder } from '../../services/groupfoldersService.js'
import axios from '@nextcloud/axios'
import NotificationError from '../../services/Notifications/NotificationError.js'
import CheckGroupfolderNameExistError from '../../Errors/Groupfolders/CheckGroupfolderNameError.js'

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
				statuscode: 400
			},
		},
	},
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
			expect(getSpy).toBeCalled()
		} catch {}
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
			expect(spy).toHaveBeenCalled()
		} catch {}
	})
	it('returns entire object received from axios.post', async () => {
		axios.post.mockResolvedValue({ data: 'foobar' })
		const res = await formatGroups({})
		expect(res).toEqual({ data: 'foobar' })
	})
})

describe('formatUsers function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.post method', () => {
		const spy = jest.spyOn(axios, 'post')
		try {
			formatUsers({})
			expect(spy).toBeCalled()
		} catch {}
	})
	it('returns entire object received from axios.post', async () => {
		axios.post.mockResolvedValue({ data: 'foobar' })
		const res = await formatUsers({})
		expect(res).toEqual({ data: 'foobar' })
	})
})

describe('checkGroupfolderNameExist function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('does call to axios.get method', () => {
		const getSpy = jest.spyOn(axios, 'get')
		try {
			checkGroupfolderNameExist('foobar')
			expect(getSpy).toBeCalled()
		} catch {}
	})
	it('throws an error if name is in the obj received from getAll call', async () => {
		const spy = jest.spyOn(CheckGroupfolderNameExistError.prototype, 'constructor')
		axios.get.mockResolvedValue(responseValue)
		try {
			await checkGroupfolderNameExist('first')
			expect(spy).toBeCalled()
		} catch {}
	})
	it('returns Promise if name does not exist', async () => {
		axios.get.mockResolvedValue(responseValue)
		await expect(checkGroupfolderNameExist('foobar')).resolves.not.toThrow()
	})
})

describe('enableAcl function', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.post method', async () => {
		axios.post.mockResolvedValue({ status: 200, ...responseValue })
		const result = await enableAcl(1)
		expect(result).toEqual(responseValue.data.ocs.data)
	})
	it('throws error if resp.status is not 200', async () => {
		axios.post.mockResolvedValue({ status: 500, ...responseValue })
		try {
			await enableAcl()
		} catch (err) {
			expect(err).toBeInstanceOf(Error)
		}
	})
})

describe('addGroupToGroupfolder', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.post method', () => {
		const spy = jest.spyOn(axios, 'post')
		try {
			addGroupToGroupfolder(1, 'SPACE-U-1')
			expect(spy).toBeCalled()
		} catch {}
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

describe('addGroupToManageACLForGroupfolder', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.post method', () => {
		const spy = jest.spyOn(axios, 'post')
		try {
			addGroupToManageACLForGroupfolder(5, 'SPACE-U-5')
			expect(spy).toBeCalled()
		} catch {}
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
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.post method', () => {
		const spy = jest.spyOn(axios, 'post')
		try {
			removeGroupToManageACLForGroupfolder(5, 'SPACE-U-5')
			expect(spy).toBeCalled()
		} catch {}
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

describe('createGroupfolder', () => {
	beforeEach(() => {
		axios.mockClear()
	})
	afterEach(() => {
		jest.resetAllMocks()
	})
	it('calls axios.post method with proper parameters', async () => {
		axios.post.mockResolvedValue(responseValue)
		await createGroupfolder('foobar')
		expect(axios.post).toHaveBeenCalledWith('/apps/groupfolders/folders', {
			mountpoint: 'foobar'
		})
	})
	it('throws error if response status is not 200', async () => {
		axios.post.mockResolvedValue(badResponseValue)
		try {
			await createGroupfolder('foobar')
		} catch (err) {
			expect(err).toBeInstanceOf(Error)
		}
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
    axios.delete.mockResolvedValue(responseValue)
    await 
  })
})
