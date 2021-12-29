<!--
  @copyright Copyright (c) 2017 Arawa

  @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
  @author 2021 Cyrille Bollu <cyrille@bollu.be>

  @license GNU AGPL version 3 or any later version

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>.
-->

<template>
	<div class="content-select-groupfolders">
		<div class="header-select-groupfolders">
			<h1 class="title-select-groupfolders">
				{{ t('workspace', 'Select groupfolders to convert in workspace') }}
			</h1>
		</div>
	</div>
</template>

<script>

import { getAll } from './services/groupfoldersService'

export default {
	name: 'SelectGroupfolders',
	components: {
	},
	data() {
		return {
		}
	},
	created() {
		this.getGroupfolders()
			.then(resultat => {
				resultat.forEach(groupfolder => {
					this.$store.dispatch('updateGroupfolders', {
						groupfolder,
					})
				})
			})
			.catch(error => {
				console.error('Error to get groupfolders to convert in space', error)
			})
	},
	methods: {
		// get groupfolders whithout spaces.
		async getGroupfolders() {
			const groupfolders = await getAll()
				.then(resp => {
					return resp
				})
				.catch(error => {
					return error
				})

			// get all spaces and "convert" in the form of Array.
			const allSpaces = []
			const spaces = this.$store.state.spaces
			const spacesKey = Object.keys(spaces)
			for (const spaceKey of spacesKey) {
				allSpaces.push(spaces[spaceKey])
			}

			const groupfoldersIdFromSpaces = allSpaces.map(space => space.groupfolderId.toString())

			// get Keys from groupfolders
			// example: [ "592", "593", "594" ]
			const groupfoldersKey = Object.keys(groupfolders)

			// Get the difference between spaces and groupfolders to get groupfoders which aren't spaces
			const groupfoldersKeysWhithoutSpace = groupfoldersKey.filter(groupfolderKey => !groupfoldersIdFromSpaces.includes(groupfolderKey))

			// Build a Groupfolders' array
			const groupfoldersWhithoutSpace = []
			for (const key of groupfoldersKeysWhithoutSpace) {
				groupfoldersWhithoutSpace.push(groupfolders[key])
			}

			return groupfoldersWhithoutSpace
		},
	},
}

</script>

<style>

.modal-container {
	display: flex !important;
	min-height: 520px !important;
	max-height: 520px !important;
}

.content-select-groupfolders {
	display: flex;
	flex-grow: 1;
	flex-direction: column;
	align-items: center;
	margin: 10px;
	min-width: 600px;
	max-width: 600px;
}

.title-select-groupfolders {
	position: relative;
	left: 20px;
	font-weight: bold;
	font-size: 18px;
}

</style>
