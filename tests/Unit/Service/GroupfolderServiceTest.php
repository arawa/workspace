<?php

namespace OCA\Workspace\Tests\Unit\Service;

use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\TestCase;
use OCP\Http\Client\IClientService;
use OCA\Workspace\Service\GroupfolderService;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\Authentication\LoginCredentials\ICredentials;

class GroupfolderServiceTest extends TestCase {

    /** @var GroupfolderService */
    private $groupfolderService;

    /** @var IClientService */
    private $clientService;

    /** @var ILogger */
    private $logger;

    /** @var string */
    private $foldername;

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var IStore */
    private $IStore;

    /** @var IClient */
    private $httpClient;

    /** @var IResponse */
    private $IResponse;

    /** @var ICredentials */
    private $login;

    private const HEADERS = [
        'Content-Type' => 'application/x-www-form-urlencoded',
        'OCS-APIRequest' => 'true',
        'Accept' => 'application/json',
        'verify' => 'false',
    ];

    public function setUp(): void {

        $this->IStore = $this->createMock(IStore::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->logger = $this->createMock(ILogger::class);
        $this->IStore = $this->createMock(IStore::class);
        $this->clientService = $this->createMock(IClientService::class);
        $this->httpClient = $this->createMock(IClient::class);
        $this->IResponse = $this->createMock(IResponse::class);
        $this->login = $this->createMock(ICredentials::class);

        $this->foldername = 'foobar';

        $this->clientService->expects($this->any())
            ->method('newClient')
            ->willReturn($this->httpClient);
        
        $this->login->expects($this->any())
            ->method('getUID')
            ->willReturn(null);

        $this->login->expects($this->any())
            ->method('getPassword')
            ->willReturn(null);

        $this->urlGenerator->expects($this->any())
            ->method('getBaseUrl')
            ->willReturn('http://www.cloud.me');


        $this->httpClient->expects($this->any())
            ->method('post')
            ->with($this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders',
                [
                    'auth' => [
                        $this->login->getUID(),
                        $this->login->getPassword()
                    ],
                    'body' => [
                        'mountpoint' => $this->foldername
                    ],
                    'headers' => self::HEADERS
                ]
            )
            ->willReturn($this->IResponse);

        $this->IResponse->expects($this->any())
            ->method('getBody')
            ->willReturn('{
                "ocs": {
                    "meta": {
                        "status": "ok",
                        "statuscode": 100,
                        "message": "OK",
                        "totalitems": "",
                        "itemsperpage": ""
                    },
                    "data": {
                        "id": 42
                    }
                }
            }');

    }

    public function testCreateGroupfolder(): void {

        $this->groupfolderService = new GroupfolderService(
            $this->urlGenerator,
            $this->clientService,
            $this->IStore,
            $this->logger
        );
               
        $result = $this->groupfolderService->create($this->foldername);

        $response = json_decode($result->getBody(), true);

        $this->assertEquals(100, $response['ocs']['meta']['statuscode']);
        $this->assertIsInt($response['ocs']['data']['id']);
    }
}