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
            $this->urlGenerator->getBaseUrl() . '/apps/workspace/workspaces/' . $id,
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'headers' => self::HEADERS
            ]
        );

        return json_decode($response->getBody(), true);
    }

    public function findAll() {
        $response = $this->httpClient->get(
            $this->urlGenerator->getBaseUrl() . '/apps/workspace/workspaces',
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

    public function updateSpaceName($newSpaceName, $spaceId) {
        $response = $this->httpClient->post(
            $this->urlGenerator->getBaseUrl() . '/apps/workspace/workspaces/' . $spaceId . '/spacename',
            [
                'auth' => [
                    $this->login->getUID(),
                    $this->login->getPassword()
                ],
                'body' => [
                    'newSpaceName' => $newSpaceName
                ],
                'headers' => self::HEADERS
            ]
        );

        return $response;
    }
}
