<?php

namespace OCA\Workspace\Middleware;

use OCA\Workspace\Service\UserService;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Middleware;
use OCP\IURLGenerator;

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
            // errorAccess template doesn't exist.
            $route = 'workspace.page.errorAccess';          
            $url = $this->urlGenerator->linkToRouteAbsolute($route, [ ]);
            
            /** 
             * TODO: Find a solution to use RedirectResponse class
             * return new RedirectResponse($url);
             * 
             * For example : return new TemplateResponse('workspace', 'errorAccess');
             */
            return new JSONResponse([
                'status' => 'forbidden',
                'msg' => 'You cannot to access this application.'
            ]);

        }

    }

}
