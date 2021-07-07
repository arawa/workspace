<?php
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
			'name' => 'page#autoComplete',
			'url' => '/api/autoComplete/{term}/{spaceId}',
			'verb' => 'GET'
		],
		[
			'name' => 'workspace_group_manager#addUserGroupUser',
			'url' => '/add/user/{uid}/toWspUserGroup/{gid}',
			'verb' => 'POST'
		],
		[
			'name' => 'workspace#createSpace',
			'url' => '/spaces',
			'verb' => 'POST'
		],
		[
			'name' => 'workspace#find',
			'url' => '/spaces/{spaceId}',
			'verb' => 'GET'
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
		[
			'name' => 'workspace#removeUserFromWorkspace',
			'url' => '/api/space/{spaceName}/user/{userId}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'workspace#changeUserRole',
			'url' => '/api/space/{spaceName}/user/{userId}',
			'verb' => 'PATCH'
		],
		[
			'name' => 'workspace#addGroupAdvancedPermissions',
			'url' => '/spaces/{folderId}/group/{gid}/acl',
			'verb' => 'POST'
		],
		[
			'name' => 'workspace_group_manager#removeUserFromGroup', 
			'url' => '/remove/user/{uid}/groups',
			'verb' => 'DELETE'
		],
		[
			'name' => 'group#create',
			// TODO move this route to /api/group/add/{group}
			'url' => '/group/add/{group}',
			'verb' => 'POST',
		],
		[
			'name' => 'group#addUser',
			// TODO move this route to /api/group/addUser/{space}
			'url' => '/group/addUser/{space}',
			'verb' => 'PATCH',
		],
		[
			'name' => 'users_manager#getUsersWorkSpace',
			'url' => '/group/{gid}/users',
			'verb' => 'GET'
		],
		[
			'name' => 'workspace#renameSpace',
			'url' => '/spaces/{spaceId}',
			'verb' => 'PATCH'
	    ],
		[
			'name' => 'space#updateSpaceName',
			'url' => '/workspaces/{spaceId}/spacename',
			'verb' => 'POST'
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
		],
		[
			'name' => 'workspace#destroy',
			'url' => '/spaces/{spaceId}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'space#updateColorCode',
			'url' => '/workspaces/{spaceId}/color',
			'verb' => 'POST'
		]
	]
];
