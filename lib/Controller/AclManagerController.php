<?php
namespace OCA\Workspace\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\Http\Client\IClientService;
use OCP\AppFramework\Http\JSONResponse;
// use OCP\IUserSession;

class AclManagerController extends Controller {
    
    private $clientService;
    
    // private $userSession;

    public function __construct($AppName, IRequest $request, IClientService $clientService, IUserSession $userSession)
    {
        parent::__construct($AppName, $request);
        $this->clientService =  $clientService;
        $this->userSession = $userSession;
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * @var string $folderId
     * @var string $gid
     */
    public function addGroupAdvancedPermissions($folderId, $gid){

        // print_r($this->userSession->isLoggedIn()); // return 1 from cli.

        $client = $this->clientService->newClient();
        
        // TOFIX: 
        // 1. Find a solution in order not to define the username & password to authentication.
        // 2. Find a solution to check if connected and add it in the conditional operator with '||' of GeneralManagerMiddleware.php
        $dataResponse = $client->post(
            'https://nc21.dev.arawa.fr/apps/groupfolders/folders/'. $folderId .'/manageACL',
            [
                // 'auth' => [
                //     'username',
                //     'password'
                // ],
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
        
        // TODO: If needed to filter like this : $response['ocs']['meta']['statuscode'].

        return new JSONResponse($response);
    }
}
