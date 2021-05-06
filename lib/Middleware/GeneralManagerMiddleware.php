<?php

namespace OCA\Workspace\Middleware;

use OCP\AppFramework\Middleware;
use OCA\Workspace\AppInfo\Application;
use OCP\IGroupManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCA\Workspace\Middleware\Exceptions\NotGeneralManagerException;

class GeneralManagerMiddleware extends Middleware{

    private $groupManager;

    private $userSession;

    public function __construct(
        IGroupManager $groupManager,
        IURLGenerator $urlGenerator,
        IUserSession $userSession
    )
    {
        $this->groupManager = $groupManager;
        $this->urlGenerator = $urlGenerator;
        $this->userSession = $userSession;
    }

    public function beforeController($controller, $methodName ){

        // TODO We must also allow space admins 
        if(!$this->groupManager->isInGroup($this->userSession->getUser()->getUID(), Application::GENERAL_MANAGER)){

            throw new NotGeneralManagerException();

        }        

    }

    // TODO: Find a solution to use this method.
    public function afterException($controller, $methodName, \Exception $exception){
        if($exception instanceof NotGeneralManagerException){
            /**
             * errorAccess tempalte is not exist.
            */ 
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
