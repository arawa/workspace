<?php
/**
 *
 * @author Cyrille Bollu <cyrille@bollu.be>
 *
 * WorkspaceAccessControlMiddleware: All controller methods may only
 * be called by application or space managers
 *
 * TODO: Add licence
 *
 */

namespace OCA\Workspace\Middleware;

use OCP\Util;
use OCP\IURLGenerator;
use OCP\AppFramework\Middleware;
use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;

class WorkspaceAccessControlMiddleware extends Middleware{

    /** @var IURLGenerator */
    private $urlGenerator;

    /** @var IUserSession */
    private $userSession;

    /** @var UserService */
    private $userService;

    public function __construct(
        IURLGenerator $urlGenerator,
	    UserService $userService
    )
    {
        $this->urlGenerator = $urlGenerator;
        $this->userService = $userService;
    }

    public function beforeController($controller, $methodName ){

        // Checks if user is member of the General managers group
        if ($this->userService->isUserGeneralAdmin()){
                return;
        }

        // Checks if user if member of a Space managers group
        if ($this->userService->isSpaceManager()){
                return;
        }

        throw new AccessDeniedException();

    }

    // TODO: Find a solution to use this method.
    public function afterException($controller, $methodName, \Exception $exception){
        if($exception instanceof AccessDeniedException){            

            Util::addScript(Application::APP_ID, 'workspace-main');		// js/workspace-main.js
            Util::addStyle(Application::APP_ID, 'workspace-style');		// css/workspace-style.css

            return new TemplateResponse("workspace", "403", []);

        }

    }

}
