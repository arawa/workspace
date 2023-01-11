import { ESPACE_USERS_PREFIX, ESPACE_GID_PREFIX } from '../../constants.js'

/**
 * @param {object} space
 * @return {string}
 */
function getUserGroup(space) {
	const groups = Object.keys(space.groups)
	const regex = new RegExp('^' + ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + '[0-9]$')

	let group = ''
	groups.forEach(groupname => {
		if (regex.test(groupname)) {
			group = ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + space.id
		} else {
			group = ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX + space.name
		}
	})

	return group
}

const UserGroup = {
	getUserGroup,
}

export default UserGroup
