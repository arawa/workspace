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

import { beforeEach, describe, expect, it } from 'vitest'
import { getSpace } from '../../services/spaceService.js'
import axios from '@nextcloud/axios'

describe('getSpace method', () => {
	beforeEach(() => {
		axios.get.mockClear()
	})

	it('Returns the workspace keyed by its name', async () => {
		axios.get.mockResolvedValue({ data: { Espace01: { id: 42, name: 'Espace01' } } })

		const result = await getSpace(42)

		expect(axios.get).toHaveBeenCalledWith(expect.stringContaining('/apps/workspace/workspaces/42'))
		expect(result).toEqual({ Espace01: { id: 42, name: 'Espace01' } })
	})

	it('Rejects when the server does not find the workspace', async () => {
		axios.get.mockRejectedValue(new Error('Request failed with status code 404'))

		await expect(getSpace(42)).rejects.toThrow('404')
	})
})
