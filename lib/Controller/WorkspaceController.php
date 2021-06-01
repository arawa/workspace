<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\AppFramework\Http;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;

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

    /** @var IUserManager */
    private $userManager;

    /** @var UserService */
    private $userService;

    public function __construct(
        $AppName,
        IClientService $clientService,
	IGroupManager $groupManager,
	ILogger $logger,
	IRequest $request,
        IURLGenerator $urlGenerator,
	IUserManager $userManager,
	UserService $userService,
        IStore $IStore
    )
    {
        parent::__construct($AppName, $request);

	$this->groupManager = $groupManager;
	$this->logger = $logger;
        $this->IStore = $IStore;
        $this->urlGenerator = $urlGenerator;
        $this->userManager = $userManager;
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
		$users = array();
		foreach($this->groupManager->get('GE-' . $space['mount_point'])->getUsers() as $user) {
			$users[$user->getDisplayName()] = $this->userService->formatUser($user, $space['id'], 'admin');
		};
		$space['admins'] = $users;
		$users = array();
		foreach($this->groupManager->get('U-' . $space['mount_point'])->getUsers() as $user) {
			$users[$user->getDisplayName()] = $this->userService->formatUser($user, $space['id'], 'user');
		};
		$space['users'] = $users;
		return $space;
		
	},$spaces);

	// TODO We still need to get the workspace color here
	
        return new JSONResponse($spacesWithUsers);
    }

	/**
	*
	* Removes a user from a workspace
	*
	* @NoAdminRequired
	*
	* @var string $spaceName
	* @var string $userName
	* 
	*/
	public function removeUserFromWorkspace(string $spaceName, string $userName) {
		if (!$this->userService->isSpaceManagerOfSpace($spaceName) && !$this->userService->isUserGeneralAdmin()) {
			return new JSONResponse(['You are not a manager for this space'], Http::STATUS_FORBIDDEN);
		}

		$user = $this->userManager->get($userName);
		$GEgroup = $this->groupManager->get(Application::ESPACE_MANAGER_01 . $spaceName);

		// If user is a general manager we may first have to remove it from the list of users allowed to use
		// the application
		if ($GEgroup->inGroup($user)) {
			$groups = $this->groupManager->getUserGroups($user);
			$found = false;
			foreach($groups as $group) {
				$groupName = $group->getGID();
				if (strpos($groupName, Application::ESPACE_MANAGER_01) === 0 &&
					$groupName !== Application::ESPACE_MANAGER_01 . $spaceName &&
					$groupName !== Application::GROUP_WKSUSER
				) {
					$found = true;
					break;
				}
			}
			if (!$found) {
				$workspaceUserGroup = $this->groupManager->get(Application::GROUP_WKSUSER);
				$workspaceUserGroup->removeUser($user);
			}
		}

		// We can now blindly remove the user from the space's admin and user groups
		$GEgroup->removeUser($user);
		$UserGroup = $this->groupManager->get(Application::ESPACE_USERS_01 . $spaceName);
		$UserGroup->removeUser($user);

	        return new JSONResponse();
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
}
