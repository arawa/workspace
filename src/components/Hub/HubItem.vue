<template>
	<a class="item-hub cursor-pointer"
		:href="url"
		@click="to($event)">
		<NcIconSvgWrapper
			class="cursor-pointer"
			:path="pathIcon"
			:svg="svg"
			:size="size" />
		<span class="cursor-pointer">
			{{ title }}
		</span>
	</a>
</template>

<script>
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { generateUrl } from '@nextcloud/router'

export default {
	name: 'HubItem',
	components: {
		NcIconSvgWrapper,
	},
	props: {
		path: {
			type: [Number, String],
			required: true,
		},
		title: {
			type: String,
			required: true,
		},
		svg: {
			type: String,
			required: false,
			default: null,
		},
		pathIcon: {
			type: String,
			required: false,
			default: null,
		},
	},
	computed: {
		url() {
			const url = generateUrl(`/apps/workspace${this.path}`)
			return url
		},
	},
	methods: {
		to() {
			this.$router.push({ path: this.path })
		},
	},
}
</script>

<style>
.cursor-pointer {
	cursor: pointer;
}

.item-hub {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-self: center;
	width: 84px;
}
</style>
