<!--
	@copyright Copyright (c) 2024 Arawa

	@author 2024 Sebastien Marinier <sebastien.marinier@arawa.fr>

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
	<span>
		<div class="app-navigation-entry selected-background space-first-background" />
		<div class="app-navigation-entry selected-background space-middle-background" />
		<div class="app-navigation-entry selected-background space-last-background" />
	</span>
</template>

<script>

export default {
	name: 'MenuItemSelector',
	mounted() {
		this.setBackgroundHeights()
	},
	methods: {
		setBackgroundHeights() {
			const parent = this.$el.parentNode.closest('.app-navigation-entry-wrapper')
			if (parent === null) {
				return
			}
			const height = parent.querySelector('.app-navigation-entry').clientHeight
			const div1 = parent.querySelector('.space-first-background')
			if (div1 !== null) {
				div1.style.minHeight = (height / 2) + 'px'
				div1.style.top = '-' + height + 'px'
			}
			const div2 = parent.querySelector('.space-middle-background')
			if (div2 !== null) {
				div2.style.top = '-' + (height / 2) + 'px'
				div2.style.bottom = (height / 2) + 'px'
			}
			const div4 = parent.querySelector('.space-last-background')
			if (div4 !== null) {
				div4.style.minHeight = (height / 2) + 'px'
			}
		}
	},
}
</script>

<style>
.app-navigation-entry.selected-background{
	position: absolute;
	background-color: var(--color-primary);
	opacity: 15%;
	z-index: -100;
}

.app-navigation-entry.space-first-background{
	top: 0;
	border-bottom-left-radius: 0;
	border-bottom-right-radius: 0;
}
.app-navigation-entry.space-middle-background{
	top: 0;
	bottom: 0;
	border-radius: 0;
}
.app-navigation-entry.space-last-background{
	bottom: 0;
	border-top-left-radius: 0;
	border-top-right-radius: 0;
}

</style>
