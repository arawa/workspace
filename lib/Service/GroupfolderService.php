<?php

namespace OCA\Workspace\Service;

use OCP\IURLGenerator;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Authentication\LoginCredentials\IStore;

class GroupfolderService {

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var IClient */
    private $httpClient;

    /** @var IStore */
    private $IStore;

    /** @var ICredentials */
    private $login;

    private const HEADERS = [
        'Content-Type' => 'application/x-www-form-urlencoded',
        'OCS-APIRequest' => 'true',
        'Accept' => 'application/json',
        'verify' => 'false',
    ];

    private const ALL_PERMISSIONS = 31;

    public function __construct(
        IURLGenerator $urlGenerator,
        IClientService $clientService,
        IStore $IStore
    ){
        $this->urlGenerator = $urlGenerator;
        $this->httpClient = $clientService->newClient();
        $this->IStore = $IStore;
        $this->login = $this->IStore->getLoginCredentials();
    }


     /**
     * @NoAdminRequired
     * @param int $folderId
     * @return object that is the response from httpClient
     */
    public function get($folderId) {
        $response = $this->httpClient->get(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/' . $folderId,
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'headers' => self::HEADERS
            ]);

        return $response;
    }
  
    /**
     * @return object that is the response from httpClient
     */
    public function getAll() {
        $response = $this->httpClient->get(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'headers' => self::HEADERS
            ]
        );

        return $response;
    }


    /**
     * @param $name the space name to create.
     * @return object that is the response from httpClient
     */
    public function create($name) {
        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'mountpoint' => $name
                ],
                'headers' => self::HEADERS
            ]
        );

        return $response;
    }

    /**
     * @param $id is the groupfolder's id.
     * @param $gid
     * @return object that is the response from httpClient
     */
    public function addGroup($id, $gid) {

        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/' . $id . '/groups',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'group' => $gid
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'OCS-APIRequest' => 'true',
                    'Accept' => 'application/json',
                ]
            ]
        );

        return $response;

    }

    /**
     * @param $id is the groupfolder's id.
     * @return object that is the response from httpClient
     */
    public function enableAcl($id) {

        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/' . $id . '/acl',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'acl' => 1
                ],
                'headers' => self::HEADERS
            ]
        );

        return $response;

    }

     /**
     * @param $id is the groupfolder's id.
     * @param $gid
     * @return object that is the response from httpClient
     * TODO: Test it if it needs.
     */
    public function enableAdvancedPermissions($id, $gid) {

        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/' . $id . '/groups/' . $gid ,
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'permissions' => self::ALL_PERMISSIONS
                ],
                'headers' => self::HEADERS
            ]
        );

        return $response;
    }

  
    /**
     * @param int $folderId
     * @param string $gid
     * @param boolean $manageAcl
     * 
     * @return object that is the response from httpClient
     */
    public function manageAcl($folderId, $gid, $manageAcl=true) {
        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/' . $folderId . '/manageACL',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'mappingType' => 'group',
                    'mappingId' => $gid,
                    'manageAcl' => $manageAcl
                ],
                'headers' => self::HEADERS
            ]
        );

        return $response;
    }

    /**
     * @NoAdminRequired
     * @param $id that is groupfolder's id
     * @return object that is the response from httpClient
     */
    public function delete($id) {
        $response = $this->httpClient->delete(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/' . $id,
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'headers' => self::HEADERS
            ]
        );

        return $response;
    }

    /**
     * @NoAdminRequired
     * @param int $folderId
     * @param string $newSpaceName
     * @return object that is the response from httpClient
     */
    public function rename($folderId, $newSpaceName) {
        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/'. $folderId .'/mountpoint',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'mountpoint' => $newSpaceName
                ],
                'headers' => self::HEADERS
            ]);
        
        return $response;
    }

    /**
     * @NoAdminRequired
     * @param int $folderId
     * @param string $gid
     * @return object that is the response from httpClient
     */
    public function attachGroup($folderId, $gid) {
        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/' . $folderId . '/groups',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'group' => $gid
                ],
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'OCS-APIRequest' => 'true',
                    'Accept' => 'application/json',
                ]
            ]
        );

        return $response;
    }

}

