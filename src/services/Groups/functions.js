/**
 * Checks if a group with the same displayName already exists in the space
 * @param {object} space Space object
 * @param {string} displayName name of the group to check
 * @return {undefined|object}
 */
export function alreadyExistsGroupName(space, displayName) {
	// check if a group with the same displayName already exists in the space
	return Object.values(space.groups).find(group => group.displayName.toLowerCase() === displayName.toLowerCase())
}
/**
 * Checks if a group with the same gid already exists in the space
 * @param {object} space Space object
 * @param {string} gid gid of the group to check
 * @return {boolean}
 */
export function alreadyExistsGroupId(space, gid) {
	// check if a group with the same displayName already exists in the space
	return Object.keys(space.groups).includes(gid)
}
