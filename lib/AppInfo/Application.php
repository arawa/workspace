<?php

declare(strict_types=1);

namespace OCA\Workspace\AppInfo;

use OCA\Workspace\Middleware\GeneralManagerMiddleware;
use \OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

class Application extends App implements IBootstrap{

    // public function __construct(array $urlParams= [])
    // {
    //     parent::__construct('workspace', $urlParams);

    //     $container = $this->getContainer();

    //     /**
    //      * Middleware
    //      */
    //     $container->registerService('GeneralManagerMiddleware', function($contain){
    //         return new GeneralManagerMiddleware(
    //             $contain->get('IGroupManager')
    //         );
    //     });

    // }

    public function register(IRegistrationContext $context){
        // ... registration logic goes here...

        // Register the composer autoloader for packages shipped by this app, if applicable
        include_once __DIR__ . '/../../vendor/autoload.php';

        $container->registerMiddleware(GeneralManagerMiddleware::class);
    }

    public function boot(IBootContext $context){
        // ... boot logic goes here ...

        $manager = $context->getAppContainer()->query(IGroupManager::class);
        $manager->registerNotifierService(Notifier::class);
    }
}