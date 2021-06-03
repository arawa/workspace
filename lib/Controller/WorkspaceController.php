<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCA\Workspace\Service\GroupfolderService;

class WorkspaceController extends Controller {
    
    /** @var IStore */
    private $IStore;

    /** @var IClient */
    private $httpClient;

    /** @var ICredentials */
    private $login;

    /** @var IGroupManager */
    private $groupManager;

    /** @var ILogger */
    private $logger;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var UserService */
    private $userService;

    /** @var GroupfolderSrvice */
    private $groupfolder;

    public function __construct(
        $AppName,
        IClientService $clientService,
	    IGroupManager $groupManager,
	    ILogger $logger,
	    IRequest $request,
        IURLGenerator $urlGenerator,
	    UserService $userService,
        IStore $IStore,
        GroupfolderService $groupfolder
    )
    {
        parent::__construct($AppName, $request);

	    $this->groupManager = $groupManager;
	    $this->logger = $logger;
        $this->IStore = $IStore;
        $this->urlGenerator = $urlGenerator;
        $this->userService = $userService;

	    $this->login = $this->IStore->getLoginCredentials();

        $this->httpClient = $clientService->newClient();

        $this->groupfolder = $groupfolder;
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
	$this->logger->debug('Fetching groupfolders');
        $response = $this->httpClient->get(
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
	$this->logger->debug('groupfolders fetched', [ 'spaces' => $spaces ]);
	
	// We only want to return those workspaces for which the connected user is a manager
	if (!$this->userService->isUserGeneralAdmin()) {
		$this->logger->debug('Filtering workspaces');
		$filteredSpaces = array_filter($spaces, function($space) {
			return $this->userService->isSpaceManagerOfSpace($space['mount_point']);
		});
        	$spaces = $filteredSpaces;
	}

	// Adds workspace users
	// TODO We still need to get the workspace color here
	$this->logger->debug('Adding users to workspaces');
	$spacesWithUsers = array_map(function($space) {
		$users = [];
		foreach($this->groupManager->get(Application::ESPACE_MANAGER_01 . $space['mount_point'])->getUsers() as $user) {
			array_push($users, $this->userService->formatUser($user, $space));
		};
		$space['admins'] = $users;
		$users = [];
		foreach($this->groupManager->get(Application::ESPACE_USERS_01 . $space['mount_point'])->getUsers() as $user) {
			array_push($users, $this->userService->formatUser($user, $space));
		};
		$space['users'] = $users;
		return $space;
		
	},$spaces);

	// TODO We still need to get the workspace color here
	
        return new JSONResponse($spacesWithUsers);
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
                    $$this->login->getPassword()
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

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @var string $folderId
     * @return JSONResponse
     */
    public function delete($folderId) {

        $groups = [];

        $responseGroupfolderGet = $this->groupfolder->get($folderId);

        $groupfolder = json_decode($responseGroupfolderGet->getBody(), true);

        if (
            !$this->userService->isSpaceManagerOfSpace($groupfolder['ocs']['data']['mount_point']) &&
            !$this->userService->isUserGeneralAdmin()
            ) 
        {
            return new JSONResponse(
                [
                    'data' => [],
                    'http' => [
                        'message' => 'You are not a manager for this space.',
                        'statuscode' => Http::STATUS_FORBIDDEN
                    ]
                ]
            );
        }
    


        $responseGroupfolderDelete = $this->groupfolder->delete($folderId);

        $groupfolderDelete = json_decode($responseGroupfolderDelete->getBody(), true);

        foreach ( array_keys($groupfolder['ocs']['data']['groups']) as $group ) {
            $groups[] = $group;
            $this->groupManager->get($group)->delete();
        }

        if ( $groupfolderDelete['ocs']['meta']['statuscode'] !== 100 ) {
            return;
        }

        return new JSONResponse([
            'http' => [
                'statuscode' => 200,
                'message' => 'The space is deleted.'
            ],
            'data' => [
                'space' => $groupfolder['ocs']['data']['mount_point'],
                'groups' => $groups,
                'state' => 'delete'
            ]
        ]);

    }
    
}
