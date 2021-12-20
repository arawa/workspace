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
			'name' => 'workspace#createSpace',
			// TODO move this route to /api/spaces
			'url' => '/spaces',
			'verb' => 'POST'
		],
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
			'name' => 'workspace#findAll',
			'url' => '/spaces',
			'verb' => 'GET'
		],
		// Change the verb DELETE by POST, because I don't send an object as params in the URL.
		// So, I must define the verb as POST and insert data.
		[
			'name' => 'workspace#destroy',
			'url' => '/api/delete/spaces',
			'verb' => 'POST'
		],
		[
			'name' => 'workspace#renameSpace',
			// TODO move this route to /api/spaces
			'url' => '/api/space/rename',
			'verb' => 'PATCH'
	    	],
		[
			'name' => 'workspace#changeUserRole',
			'url' => '/api/space/{spaceId}/user/{userId}',
			'verb' => 'PATCH'
		],
		[
			'name' => 'group#create',
			'url' => '/api/group/{gid}',
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
			'name' => 'group#addUser',
			'url' => '/api/group/addUser/{spaceId}',
			'verb' => 'PATCH',
		],
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
		// The following route is there to prevent redirection to NC's general homepage
		// when reloading a page in the application (If we don't add it all pages that
		// don't have a route registered here redirect to NC's general homepage upon refresh)
		[
			'name' => 'page#index',
			'url' => '/{path}',
			'verb' => 'GET',
			'requirements' => array('path' => '.*'),
			'defaults' => array('path' => 'dummy'),
		]
	]
];
