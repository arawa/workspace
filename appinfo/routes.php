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
// A {gid} reaches its controller still encoded once, which the controller decodes: the front
// end encodes it twice (encodeGid() in src/services/Groups/functions.js), so that an encoded "/"
// in a gid gets past Apache (AllowEncodedSlashes Off)
return [
	'routes' => [
		[
			'name' => 'page#index',
			'url' => '/',
			'verb' => 'GET',
		],
		[
			'name' => 'workspace#findAll',
			'url' => '/workspaces',
			'verb' => 'GET',
		],
		[
			'name' => 'workspace#createWorkspace',
			'url' => '/workspaces',
			'verb' => 'POST',
		],
		[
			'name' => 'workspace#renameSpace',
			'url' => '/workspaces/{spaceId}',
			'verb' => 'PATCH',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'workspace#destroy',
			'url' => '/workspaces/{spaceId}',
			'verb' => 'DELETE',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'space#updateColorCode',
			'url' => '/workspaces/{spaceId}/color',
			'verb' => 'PATCH',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'workspace#updateQuota',
			'url' => '/workspaces/{spaceId}/quota',
			'verb' => 'PATCH',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'workspace#getUsers',
			'url' => '/workspaces/{spaceId}/users',
			'verb' => 'GET',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'workspace#lookupUsers',
			'url' => '/workspaces/{spaceId}/users/search',
			'verb' => 'POST',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'workspace#changeUserRole',
			'url' => '/workspaces/{spaceId}/users/{userId}/role',
			'verb' => 'PATCH',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'group#removeUserFromWorkspace',
			'url' => '/workspaces/{spaceId}/users/{user}',
			'verb' => 'DELETE',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'workspace#getAdmins',
			'url' => '/workspaces/{spaceId}/managers',
			'verb' => 'GET',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'group#create',
			'url' => '/workspaces/{spaceId}/groups',
			'verb' => 'POST',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'group#rename',
			'url' => '/workspaces/{spaceId}/groups/{gid}',
			'verb' => 'PATCH',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'group#delete',
			'url' => '/workspaces/{spaceId}/groups/{gid}',
			'verb' => 'DELETE',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'group#attachGroupToSpace',
			'url' => '/workspaces/{spaceId}/groups/{gid}/attach',
			'verb' => 'POST',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'group#addUser',
			'url' => '/workspaces/{spaceId}/groups/{gid}/users',
			'verb' => 'POST',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'group#removeUser',
			'url' => '/workspaces/{spaceId}/groups/{gid}/users/{user}',
			'verb' => 'DELETE',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'connectedGroup#addGroup',
			'url' => '/workspaces/{spaceId}/connected-groups',
			'verb' => 'POST',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'connectedGroup#removeGroup',
			'url' => '/workspaces/{spaceId}/connected-groups/{gid}',
			'verb' => 'DELETE',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'fileCSV#import',
			'url' => '/workspaces/{spaceId}/import-users/local',
			'verb' => 'POST',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'fileCSV#getFromFiles',
			'url' => '/workspaces/{spaceId}/import-users/files',
			'verb' => 'POST',
			'requirements' => ['spaceId' => '\d+'],
		],
		[
			'name' => 'group#search',
			'url' => '/groups',
			'verb' => 'GET',
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
