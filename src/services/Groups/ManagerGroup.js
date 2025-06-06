/**
 * copyright Copyright (c) 2017 Arawa
 *
 * author 2023 Baptiste Fotia <baptiste.fotia@arawa.fr>
 *
 * license GNU AGPL version 3 or any later version
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

import { PREFIX_MANAGER } from '../../constants.js'

/**
 * @param {object} space present the space entity
 * @return {string}
 */
function getGid(space) {
	const groups = Object.keys(space.groups)

	const spaceGeneralGroupRegex = new RegExp('^' + PREFIX_MANAGER + '[0-9]*$')

	const groupFound = groups.find(function(group) {
		if (spaceGeneralGroupRegex.test(group)) {
			return true
		}

		return false
	})

	return groupFound
}

const ManagerGroup = {
	getGid,
}

export default ManagerGroup
