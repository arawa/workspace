<?php

namespace OCA\Workspace\AppInfo;

use OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware;
use OCP\AppFramework\App;
use OCP\IURLGenerator;
use OCP\IUser;

class Application extends App {
        public const APP_ID = 'workspace';

        public const GENERAL_MANAGER = "GeneralManager";

        public function __construct(array $urlParams=[] ) {
                parent::__construct(self::APP_ID, $urlParams);

                $container = $this->getContainer();

                $container->registerService('WorkspaceAccessControlMiddleware', function($c){
                    return new WorkspaceAccessControlMiddleware(
                        $c->query(IURLGenerator::class),
                        $c->query(IUser::class)
                    );
                });

                $container->registerMiddleware('OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware');
        }
}
