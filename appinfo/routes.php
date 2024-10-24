<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
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

/**
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Workspace\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */
return [
	'routes' => [
		[
			'name' => 'page#index',
			'url' => '/',
			'verb' => 'GET',
		],
		[
			'name' => 'connectedGroup#addConnectedGroup',
			'url' => '/spaces/{spaceId}/connected-group',
			'verb' => 'POST'
		],
		[
			'name' => 'connectedGroup#getConnectedGroupsFromSpaceId',
			'url' => '/spaces/{spaceId}/connected-group',
			'verb' => 'GET'
		],
		[
			'name' => 'connectedGroup#getConnectedGroups',
			'url' => '/connected-group',
			'verb' => 'GET'
		],
		[
			'name' => 'workspace#addGroupsInfo',
			'url' => '/api/workspace/formatGroups',
			'verb' => 'POST'
		],
		[
			'name' => 'workspace#addUsersInfo',
			'url' => '/api/workspace/formatUsers',
			'verb' => 'POST'
		],
		[
			'name' => 'workspace#lookupUsers',
			'url' => '/api/autoComplete/{term}/{spaceId}',
			'verb' => 'POST'
		],
		[
			'name' => 'workspace#createWorkspace',
			'url' => '/spaces',
			'verb' => 'POST'
		],
		/**
		 * @todo Decomment these lines to convert a groupfolder to workspace.
		 * This may be possible from NC25 or NC26.
		 */
		// [
		// 	'name' => 'group#transferUsersToGroups',
		// 	'url' => '/spaces/{spaceId}/transfer-users',
		// 	'verb' => 'POST'
		// ],
		[
			'name' => 'space#find',
			'url' => '/workspaces/{id}',
			'verb' => 'GET'
		],
		[
			'name' => 'space#findAll',
			'url' => '/workspaces',
			'verb' => 'GET'
		],
		[
			'name' => 'connectedGroup#addGroup',
			'url' => '/spaces/{spaceId}/connected-groups/{gid}',
			'verb' => 'POST',
		],
		[
			'name' => 'connectedGroup#removeGroup',
			'url' => '/spaces/{spaceId}/connected-groups/{gid}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'workspace#findAll',
			'url' => '/spaces',
			'verb' => 'GET'
		],
		[
			'name' => 'workspace#destroy',
			'url' => '/spaces/{spaceId}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'workspace#renameSpace',
			'url' => '/spaces/{spaceId}',
			'verb' => 'PATCH'
		],
		[
			'name' => 'workspace#changeUserRole',
			'url' => '/api/space/{spaceId}/user/{userId}',
			'verb' => 'PATCH'
		],
		[
			'name' => 'group#create',
			'url' => '/api/group',
			'verb' => 'POST',
		],
		[
			'name' => 'group#attachGroupToSpace',
			'url' => '/spaces/{spaceId}/group-attach',
			'verb' => 'POST',
		],
		[
			'name' => 'group#delete',
			'url' => '/api/group/{gid}',
			'verb' => 'DELETE',
		],
		[
			'name' => 'group#rename',
			'url' => '/api/group/{gid}',
			'verb' => 'PATCH',
		],
		[
			'name' => 'group#search',
			'url' => '/groups',
			'verb' => 'GET',
		],
		[
			'name' => 'group#addUser',
			/**
			 * @todo Rewrite this route
			 * I should write : /groups/{gid}/users
			 */
			'url' => '/api/group/addUser/{spaceId}',
			'verb' => 'POST',
		],
		// [
		// 	'name' => 'fileCSV#import',
		// 	'url' => '/file/csv/import-data',
		// 	'verb' => 'POST',
		// ],
		// [
		// 	'name' => 'fileCSV#getFromFiles',
		// 	'url' => '/file/csv/import-from-files',
		// 	'verb' => 'POST',
		// ],
		[
			'name' => 'space#updateColorCode',
			'url' => '/workspaces/{spaceId}/color',
			'verb' => 'POST'
		],
		[

			'name' => 'group#removeUser',
			'url' => '/api/group/delUser/{spaceId}',
			'verb' => 'PATCH',
		],
		[
			'name' => 'workspace#getUsers',
			'url' => '/spaces/{spaceId}/users',
			'verb' => 'GET',
		],
		[
			'name' => 'group#removeUserFromWorkspace',
			'url' => '/spaces/{spaceId}/users/{user}/groups',
			'verb' => 'PATCH',
		],
		// The following route is there to prevent redirection to NC's general homepage
		// when reloading a page in the application (If we don't add it all pages that
		// don't have a route registered here redirect to NC's general homepage upon refresh)
		[
			'name' => 'page#index',
			'url' => '/{path}',
			'verb' => 'GET',
			'requirements' => ['path' => '.*'],
			'defaults' => ['path' => 'dummy'],
			'postfix' => 'catchall',
		]
	]
];
