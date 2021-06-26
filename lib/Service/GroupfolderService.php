<?php

namespace OCA\Workspace\Service;

use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\Authentication\LoginCredentials\ICredentials;

class GroupfolderService {

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var IClient */
    private $httpClient;

    /** @var IStore */
    private $IStore;

    /** @var ILogger */
    private $logger;

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
        IStore $IStore,
        ILogger $logger
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->httpClient = $clientService->newClient();
        $this->IStore = $IStore;
        $this->login = $this->IStore->getLoginCredentials();
        $this->logger = $logger;
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
	      $this->logger->debug('calling groupfolder "create groupfolder" API');
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

	      $this->logger->debug('calling groupfolder "assign group to groupfolder" API');
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

      	$this->logger->debug('calling groupfolder "enable ACL" API');
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
     * @param int $folderId the space name to delete.
     * @return object that is the response from httpClient
     * 
    */
    public function delete($folderId) {
        $this->logger->debug('calling groupfolder "delete groupfolder" API');
        $response = $this->httpClient->delete(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/' . $folderId,
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
     * @param int $folderId
     * @return object that is the response from httpClient
     */
    public function get($folderId) {
        $response = $this->httpClient->get(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/' . $folderId,
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
     * Gets a groupfolder's name from its ID
     * @param int $folderId The id of the groupfolder
     * @return string The name of the groupfolder
     */
    public function getName($folderId) {
        $response = $this->httpClient->get(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/' . $folderId,
            [
              'auth' => [
                  $this->login->getUID(),
                  $this->login->getPassword()
              ],
              'headers' => self::HEADERS
        ]);

	$groupfolder = json_decode($response->getBody(), true);

	// TODO Error management

	return $groupfolder['ocs']['data']['mount_point'];

    }

     /**
     * @param $id is the groupfolder's id.
     * @param $gid
     * @return object that is the response from httpClient
     * TODO: Test it if it needs.
     */
    public function enableAdvancedPermissions($id, $gid) {

        $this->logger->debug('calling groupfolder "enable advanced permissions" API');
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
	      $this->logger->debug('calling groupfolder "manage ACL" API');
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
     * @param int $folderId
     * @param string $newSpaceName
     * @return object that is the response from httpClient
     */
    public function rename($folderId, $newSpaceName) {
        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/'. $folderId .'/mountpoint',
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
     * @param int $folderId
     * @param string $gid
     * @return object that is the response from httpClient
     */
    public function attachGroup($folderId, $gid) {
        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders/' . $folderId . '/groups',
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
     * @param string $spacename
     * @return bool true if exist, otherwise false.
     */
    public function checkGroupfolderNameExist($spacename) {

        $responseGroupfolders = $this->getAll();

        $groupfolders = json_decode($responseGroupfolders->getBody(), true);

        $mountpoints = array_values(
            array_map(
                function ($groupfolder){
                    return strtoupper($groupfolder['mount_point']);
                },
                $groupfolders['ocs']['data']
            )
        );

        return in_array(strtoupper($spacename), $mountpoints);

    }

}

