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
			console.error('Error to get all spaces', e)
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

export function formatGroups(space) {
	const data = axios.post(generateUrl('/apps/workspace/api/workspace/formatGroups'), { workspace: space })
		.then(resp => {
			return resp
		})
		.catch(error => {
			console.error('Error POST to format space\'s groups', error)
		})
	return data
}
