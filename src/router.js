import Vue from 'vue'
import Router from 'vue-router'
import { generateUrl } from '@nextcloud/router'
import GroupDetails from './GroupDetails'
import Home from './Home'
import SpaceDetails from './SpaceDetails'
import SpaceTable from './SpaceTable'
import error403 from './error403'

Vue.use(Router)

export default new Router({
	mode: 'history',
	base: generateUrl('/apps/workspace/'),
	linkActiveClass: 'active',
	routes: [
		{
			path: '/',
			name: 'home',
			component: Home,
			children: [
				{
					path: '',
					component: SpaceTable,
				},
				{
					path: 'workspace/:space',
					component: SpaceDetails,
				},
				{
					path: 'group/:space/:group',
					component: GroupDetails,
				},
				{
					path: 'unauthorized',
					component: error403,
				},
			],
		},
	],
})
