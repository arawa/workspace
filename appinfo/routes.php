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
			'name' => 'workspace#destroy',
			'url' => '/spaces/{spaceId}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'workspace#rename',
			// TODO move this route to /api/spaces
			'url' => '/spaces/{folderId}',
			'verb' => 'PATCH'
	    	],
		[
			'name' => 'workspace#removeUserFromWorkspace',
			'url' => '/api/space/{spaceId}/user/{userId}',
			'verb' => 'DELETE'
		],
		[
			'name' => 'workspace#changeUserRole',
			'url' => '/api/space/{spaceId}/user/{userId}',
			'verb' => 'PATCH'
		],
		[
			'name' => 'workspace#addGroupAdvancedPermissions',
			// TODO move this route to /api/spaces
			'url' => '/spaces/{folderId}/group/{gid}/acl',
			'verb' => 'POST'
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
			'name' => 'workspace#renameSpace',
			'url' => '/spaces/{spaceId}',
			'verb' => 'PATCH'
	    	],
		[
			'name' => 'space#updateSpaceName',
			'url' => '/workspaces/{spaceId}/spacename',
			'verb' => 'POST'
	    	],
		[
			'name' => 'space#updateColorCode',
			'url' => '/workspaces/{spaceId}/color',
			'verb' => 'POST'
		],

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
		],
	]
];
