<?php

namespace OCA\Workspace\Middleware;

use OCP\AppFramework\Middleware;
use OCP\IGroupManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCA\Workspace\Middleware\Exceptions\NotGeneralManagerException;

class GeneralManagerMiddleware extends Middleware{

    private $GENERAL_MANAGER = "GeneralManager";

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

        if(! $this->groupManager->isInGroup($this->userSession->getUser()->getUID(), $this->GENERAL_MANAGER)){

            throw new NotGeneralManagerException();

        }        

    }

    // TODO: Find a solution to use this method.
    public function afterException($controller, $methodName, \Exception $exception){
        if($exception instanceof NotGeneralManagerException){
            $route = 'workspace.page.errorAccess';          
            
            $url = $this->urlGenerator->linkToRouteAbsolute($route, [ ]);
            
            return new TemplateResponse('workspace', 'errorAccess');
            // TODO: Find a solution to use RedirectResponse class
            // return new RedirectResponse($url);

        }

    }

}