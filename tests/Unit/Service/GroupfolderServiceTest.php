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

	/** @var IResponseGetAll */
	private $IResponseGetAll;

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
        $this->IResponseGetAll = $this->createMock(IResponse::class);
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

		$this->httpClient->expects($this->any())
			->method('get')
			->with($this->urlGenerator->getBaseUrl() . '/index.php/apps/groupfolders/folders',
				[
					'auth' => [
                        $this->login->getUID(),
                        $this->login->getPassword()
                    ],
                    'headers' => self::HEADERS				
				])
			->willReturn($this->IResponseGetAll);

		$this->IResponseGetAll->expects($this->any())
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
					"500": {
					  "id": 500,
					  "mount_point": "Test",
					  "groups": [],
					  "quota": "-3",
					  "size": 0,
					  "acl": false,
					  "manage": []
					},
					"501": {
					  "id": 501,
					  "mount_point": "Lanfeust",
					  "groups": {
						"SPACE-GE-175": 31,
						"SPACE-U-175": 31
					  },
					  "quota": "-3",
					  "size": 0,
					  "acl": true,
					  "manage": [
						{
						  "type": "group",
						  "id": "SPACE-GE-175",
						  "displayname": "GE-175"
						}
					  ]
					},
					"502": {
					  "id": 502,
					  "mount_point": "Brocéliande",
					  "groups": {
						"SPACE-GE-176": 31,
						"SPACE-U-176": 31
					  },
					  "quota": "-3",
					  "size": 0,
					  "acl": true,
					  "manage": [
						{
						  "type": "group",
						  "id": "SPACE-GE-176",
						  "displayname": "GE-176"
						}
					  ]
					},
					"503": {
					  "id": 503,
					  "mount_point": "Windows",
					  "groups": [],
					  "quota": "-3",
					  "size": 0,
					  "acl": false,
					  "manage": []
					}
				  }
				}
			  }'
			);

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

    public function testGetAllGroupfolder(): void {

        $this->groupfolderService = new GroupfolderService(
            $this->urlGenerator,
            $this->clientService,
            $this->IStore,
            $this->logger
        );

        $response = $this->groupfolderService->getAll();

		$this->assertIsArray($response);
    }
}