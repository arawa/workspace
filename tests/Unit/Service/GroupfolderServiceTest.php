<?php

namespace OCA\Workspace\Tests\Unit\Service;

use OCP\ILogger;
use OCP\IUserSession;
use OCP\IURLGenerator;
use OCP\Http\Client\IClient;
use PHPUnit\Framework\TestCase;
use OCP\Http\Client\IClientService;
use OCA\Workspace\Service\GroupfolderService;
use OCP\Authentication\LoginCredentials\IStore;
use OCP\Authentication\LoginCredentials\ICredentials;
use OCP\Http\Client\IResponse;

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
    
    public function setUp(): void {

        $this->IStore = $this->createMock(IStore::class);
        $this->urlGenerator = $this->createMock(IURLGenerator::class);
        $this->logger = $this->createMock(ILogger::class);
        $this->IStore = $this->createMock(IStore::class);
        $this->ICredentials = $this->createMock(ICredentials::class);
        $this->clientService = $this->createMock(IClientService::class);
        $this->httpClient = $this->createMock(IClient::class);
        $this->userSession = $this->createMock(IUserSession::class);
        $this->IResponse = $this->createMock(IResponse::class);

        $this->clientService->expects($this->any())
            ->method('newClient')
            ->willReturn($this->httpClient);        

        $this->httpClient->expects($this->any())
            ->method('post')
            ->willReturn($this->IResponse);

        $this->IResponse->expects($this->any())
            ->method('getBody')
            ->willReturn('{
                "ocs": {
                    "meta": {
                        "statuscode": 100
                    }
                }
            }');

        $this->foldername = 'foobar';
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
    }
}