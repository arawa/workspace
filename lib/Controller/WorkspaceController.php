<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;

class WorkspaceController extends Controller {
    
    /** @var IStore */
    private $IStore;

    /** @var IClient */
    private $httpClient;

    /** @var ICredentials */
    private $login;

    /** @var IGroupManager */
    private $groupManager;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var UserService */
    private $userService;

    public function __construct(
        $AppName,
        IClientService $clientService,
        IGroupManager $groupManager,
        IRequest $request,
        IURLGenerator $urlGenerator,
	    UserService $userService,
        IStore $IStore
    )
    {
        parent::__construct($AppName, $request);

	$this->groupManager = $groupManager;
        $this->IStore = $IStore;
        $this->urlGenerator = $urlGenerator;
        $this->userService = $userService;

	$this->login = $this->IStore->getLoginCredentials();

        $this->httpClient = $clientService->newClient();
    }

    /**
     *
     * Returns a list of all the workspaces that the connected user
     * may use.
     *
     * @NoAdminRequired
     * 
     */
    public function getUserWorkspaces() {
        
	// Gets all groupfolders
        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders?format=json',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'OCS-APIRequest' => 'true',
                        'Accept' => 'application/json',
                ],
		'verify' => false,
            ]
        );

	// TODO Check response first
	// TODO Filter to show only workspaces, not regular groupfolders
	
	$spaces = json_decode($response->getBody(), true);
	$spaces = $spaces['ocs']['data'];
	
	// We only want to return those workspaces for which the connected user is a manager
	if (!$this->userService->isUserGeneralAdmin()) {
		$filteredSpaces = array_filter($spaces, function($space) {
			return $this->userService->isSpaceManagerOfSpace($space['mount_point']);
		});
        	$spaces = $filteredSpaces;
	}

	// Adds workspace users
	// TODO We still need to get the workspace color here
	$spacesWithUsers = array_map(function($space) {
		$space['admins'] = $this->groupManager->get('GE-' . $space['mount_point'])->getUsers();
		$space['users'] = $this->groupManager->get('U-' . $space['mount_point'])->getUsers();
		return $space;
		
	},$spaces);

        return new JSONResponse($spacesWithUsers);
    }

    
    /**
     * TODO: I'm finish all calls API (REST) to create a workspace.
     * I should create a json response followings calls API (REST) and manage different errors.
     * Then, call the route from front-end.
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @var string $spaceName
     * @return JSONResponse with informations from new workspace - { 'msg': Sting, 'statuscode': Int, 'data': Object }
     * @example { 'msg': 'Worspace created', 'statuscode': 201, 'data': Object }
     */
    public function create($spaceName) {

        $newSpaceManagerGroup = $this->groupManager->createGroup('GE-' . $spaceName);
        $newSpaceUsersGroup = $this->groupManager->createGroup('U-' . $spaceName);

        // TODO: add admin group to the app’s « limit to groups » field

        $dataResponseCreateGroupFolder = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'mountpoint' => $spaceName
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'OCS-APIRequest' => 'true',
                    'Accept' => 'application/json',
                ]
            ]
        );

        // TODO: Manage the error case creating groupfolder

        $responseCreateGroupFolder = json_decode($dataResponseCreateGroupFolder->getBody(), true);
        
        $dataNewGroupFolder = $responseCreateGroupFolder['ocs']['data'];

        $dataResponseAssignSpaceManagerGroup = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/' . $dataNewGroupFolder['id'] . '/groups',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'group' => $newSpaceManagerGroup->getGID()
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'OCS-APIRequest' => 'true',
                    'Accept' => 'application/json',
                ]
            ]
        );

        $responseAssignSpaceManagerGroup = json_decode($dataResponseAssignSpaceManagerGroup->getBody(), true);

        // TODO: Manage the error case assigning space managere group to groupfolder

        $dataResponseAssignSpaceUsersGroup = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/' . $dataNewGroupFolder['id'] . '/groups',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'group' => $newSpaceUsersGroup->getGID()
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'OCS-APIRequest' => 'true',
                    'Accept' => 'application/json',
                ]
            ]
        );

        $responseAssignSpaceUsersGroup = json_decode($dataResponseAssignSpaceUsersGroup->getBody(), true);

        // TODO: Manage the error case assigning space users group to groupfolder

        $dataEnableACLGroupFolder = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/' . $dataNewGroupFolder['id'] . '/acl',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'acl' => 1
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'OCS-APIRequest' => 'true',
                    'Accept' => 'application/json',
                ]
            ]
        );

        $responseEnableACLGroupFolder = json_decode($dataEnableACLGroupFolder->getBody(), true);

        // TODO: Manage the error case enabling acl to groupfolder

        $dataEnableAdvancedPermissionsGroupFolder = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/' . $dataNewGroupFolder['id'] . '/groups/' . $newSpaceManagerGroup->getGID() ,
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'permissions' => 31
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'OCS-APIRequest' => 'true',
                    'Accept' => 'application/json',
                ]
            ]
        );

        $responseEnableAdvancedPermissionsGroupFolder = json_decode($dataEnableAdvancedPermissionsGroupFolder->getBody(), true);

        // TODO: Manage the error case enabling advanced permissions to groupfolder

        return [];
    }


    /**
     *
     * TODO This is a single API call. It should probably be moved to the frontend
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @var string $folderId
     * @var string $gid
     */
    public function addGroupAdvancedPermissions($folderId, $gid){

        $dataResponse = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/'. $folderId .'/manageACL',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                        'mappingType' => 'group',
                        'mappingId' => $gid,
                        'manageAcl' => true
                ],
                'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                        'OCS-APIRequest' => 'true',
                        'Accept' => 'application/json',
                ]
            ]
        );

        $response = json_decode($dataResponse->getBody(), true);

        return new JSONResponse($response);
    }
}
