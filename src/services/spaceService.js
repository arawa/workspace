import axios from '@nextcloud/axios'
import { ESPACE_GID_PREFIX, ESPACE_MANAGERS_PREFIX, ESPACE_USERS_PREFIX } from '../constants'
import { generateUrl } from '@nextcloud/router'

// Param: string spaceName
// Param: int folderId
// return object
export function createSpace(spaceName, folderId) {
	const result = axios.post(generateUrl('/apps/workspace/spaces'),
		{
			spaceName,
			folderId
		})
		.then(resp => {
			return resp.data
		})
		.catch(error => {
			console.error('createSpace error', error)
		})
	return result
}

// Param string group
// return string
export function isSpaceManagers(group) {
	const SPACE_MANAGER_REGEX = new RegExp('^' + ESPACE_GID_PREFIX + ESPACE_MANAGERS_PREFIX)
	return SPACE_MANAGER_REGEX.test(group)
}

// Param string group
// return string
export function isSpaceUsers(group) {
	const SPACE_USER_REGEX = new RegExp('^' + ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX)
	return SPACE_USER_REGEX.test(group)
}
