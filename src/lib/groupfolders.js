import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

export function getAll() {
	const data = axios.get(generateUrl('/index.php/apps/groupfolders/folders'))
		.then(resp => {
			if (resp.data.ocs.meta.status === 'ok') {
				return resp.data.ocs.data
			}
		})
		.catch(e => {
			console.debug('Error to get all spaces', e)
		})
	return data
}

export function get(groupfolderId) {
	const data = axios.get(generateUrl(`/index.php/apps/groupfolders/folders/${groupfolderId}`))
		.then(resp => {
			if (resp.data.ocs.meta.status === 'ok') {
				const workspace = resp.data.ocs.data
				return workspace
			}
		})
		.catch(e => {
			console.error('Error to get one space', e)
		})
	return data
}

// generate an error
export function formatGroups(workspace) {
	const data = axios.post(generateUrl('/api/workspace/formatGroups'), { workspace })
		.then(resp => {
			console.debug('formatGroups resp', resp)
			return resp
		})
		.catch(e => {
			console.debug('error from formatGroups', e)
		})
	return data
}
