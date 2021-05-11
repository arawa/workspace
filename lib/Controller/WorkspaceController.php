<?php
namespace OCA\Workspace\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\IURLGenerator;

class WorkspaceController extends Controller {
    
    /** @var IStore */
    private $IStore;

    /** @var IClient */
    private $httpClient;

    /** @var ICredentials */
    private $login;

    /** @var IURLGenerator */
    private $urlGenerator;

    public function __construct(
        $AppName,
        IClientService $clientService,
	IRequest $request,
        IURLGenerator $urlGenerator,
        IStore $IStore
    )
    {
        parent::__construct($AppName, $request);

        $this->urlGenerator = $urlGenerator;
        $this->IStore = $IStore;

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
                ]
            ]
        );

	// TODO Check response first
	$spaces = json_decode($response->getBody());

	// TODO Filter to show only workspaces
	//
	// Only returns those workspaces for which the connected user is a manager
	if (!$this->userService->isUserGeneralAdmin()) {

	}

        return new JSONResponse($response);
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

        $login = $this->IStore->getLoginCredentials();

        $client = $this->clientService->newClient();
        
        $dataResponse = $client->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/'. $folderId .'/manageACL',
            [
                'auth' => [
                    $login->getUID(),
                    $login->getPassword()
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

        $jsonResponse = $dataResponse->getBody();
        $response = json_decode($jsonResponse, true);
        
        return new JSONResponse($response);
    }
}
