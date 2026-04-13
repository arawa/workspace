import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

/**
 *
 * @param {string} appName the name of the app to get the icon
 * @return {Promise} a promise that resolves to the app icon path
 */
export function getAppIcon(appName) {
	const result = axios.get(generateUrl(`/apps/workspace/hub/app/${appName}/icon`))
		.then(resp => {
			return resp.data
		})
	return result
}
