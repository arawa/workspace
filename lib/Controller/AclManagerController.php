<?php
namespace OCA\Workspace\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\Http\Client\IClientService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IURLGenerator;
use OCP\Authentication\LoginCredentials\IStore;

class AclManagerController extends Controller {
    
    private $clientService;
    
    private $urlGenerator;

    private $IStore;

    public function __construct(
        $AppName,
        IRequest $request,
        IClientService $clientService,
        IURLGenerator $urlGenerator,
        IStore $IStore
    )
    {
        parent::__construct($AppName, $request);
        $this->clientService =  $clientService;
        $this->urlGenerator = $urlGenerator;
        $this->IStore = $IStore;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @var string $folderId
     * @var string $gid
     */
    public function addGroupAdvancedPermissions($folderId, $gid, $token){

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
