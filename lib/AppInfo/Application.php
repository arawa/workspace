<?php
/**
 * @copyright 2021 Arawa <TODO>
 *
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 * @license <TODO>
 */

namespace OCA\Workspace\AppInfo;

use OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\App;
use OCP\IURLGenerator;

class Application extends App {

        public const APP_ID = 'workspace';
	public const GROUP_WKSUSER = 'Workspace users';
        public const GENERAL_MANAGER = "GeneralManager";
	// TODO Remove the '_01' suffix 
        public const ESPACE_MANAGER_01 = "GE-";
        public const ESPACE_USERS_01 = "U-";

        public function __construct(array $urlParams=[] ) {
                parent::__construct(self::APP_ID, $urlParams);

                $container = $this->getContainer();

                $container->registerService('WorkspaceAccessControlMiddleware', function($c){
                    return new WorkspaceAccessControlMiddleware(
                        $c->query(IURLGenerator::class),
                        $c->query(UserService::class)
                    );
                });

                $container->registerMiddleware('OCA\Workspace\Middleware\WorkspaceAccessControlMiddleware');
        }
}
