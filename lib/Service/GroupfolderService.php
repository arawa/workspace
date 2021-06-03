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
     * @param int $folderId the space name to delete.
     * @return object that is the response from httpClient
     * 
     */
    public function delete($folderId) {
        $response = $this->httpClient->delete(
            $this->urlGenerator->getBaseUrl() . '/apps/groupfolders/folders/' . $folderId,
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

}