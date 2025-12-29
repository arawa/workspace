<template>
	<NcEmptyContent
		:name="t('workspace', 'No workspace')">
		<template #icon>
			<NcIconSvgWrapper :name="iconName" :path="iconPath" />
		</template>
		<template #description>
			{{ message }}
		</template>
	</NcEmptyContent>
</template>

<script>
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { mdiFolderOff } from '@mdi/js'

export default {
	name: 'WorkspaceContentEmpty',
	components: {
		NcEmptyContent,
		NcIconSvgWrapper,
	},
	props: {
		message: {
			type: String,
			required: true,
		},
		iconPath: {
			type: String,
			required: false,
			default: mdiFolderOff,
		},
		iconName: {
			type: String,
			required: false,
			default: 'folders-off',
		},
	},
	data() {
		return {
			mdiFolderOff,
		}
	},
}
</script>
