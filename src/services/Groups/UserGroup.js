import { ESPACE_USERS_PREFIX, ESPACE_GID_PREFIX, ESPACE_USERS_GROUP_PREFIX } from '../../constants.js'

/**
 * @param {object} space
 * @return {string}
 */
function getUserGroup(space) {
	const groups = Object.keys(space.groups)

	let group = ''
	groups.forEach(groupname => {
		if (groupname.startsWith(ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX)) {
			group = ESPACE_GID_PREFIX + ESPACE_USERS_PREFIX
		} else if (groupname.startsWith(ESPACE_GID_PREFIX + ESPACE_USERS_GROUP_PREFIX)) {
			group = ESPACE_GID_PREFIX + ESPACE_USERS_GROUP_PREFIX
		}
	})

	return group
}

const UserGroup = {
	getUserGroup,
}

export default UserGroup
