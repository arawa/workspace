<?php

namespace OCA\Workspace\Service;

use OCP\Authentication\LoginCredentials\IStore;
use OCP\Http\Client\IClientService;
use OCP\IURLGenerator;

class WorkspaceService {

    private const HEADERS = [
        'Content-Type' => 'application/x-www-form-urlencoded',
        'OCS-APIRequest' => 'true',
        'Accept' => 'application/json',
        'verify' => 'false',
    ];

    public function __construct(
        IURLGenerator $urlGenerator,
        IClientService $clientService,
        IStore $IStore
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->httpClient = $clientService->newClient();
        $this->IStore = $IStore;
        $this->login = $this->IStore->getLoginCredentials();
    }

    public function get($id){

        $response = $this->httpClient->get(
            $this->urlGenerator->getBaseUrl() . '/apps/workspace/space/db/' . $id,
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

    public function findAll() {
        $response = $this->httpClient->get(
            $this->urlGenerator->getBaseUrl() . '/apps/workspace/spaces/db',
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
}
