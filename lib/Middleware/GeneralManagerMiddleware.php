<?php

namespace OCA\Workspace\Middleware;

use OCP\AppFramework\Middleware;
use OCP\IGroupManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IURLGenerator;
use OCP\IUser;
use OCA\Workspace\Middleware\Exceptions\NotGeneralManagerException;

class GeneralManagerMiddleware extends Middleware{

    private $GENERAL_MANAGER = "GeneralManager";

    private $groupManager;

    public function __construct(IGroupManager $groupManager, IURLGenerator $urlGenerator)
    {
        $this->groupManager = $groupManager;
        $this->urlGenerator = $urlGenerator;
    }

    public function beforeController($controller, $methodName, string $userId ){

        if(! $this->groupManager->isInGroup($userId, $this->GENERAL_MANAGER)){

            throw new NotGeneralManagerException();

        }        

    }

    // TODO: Find a solution to use this method.
    public function afterException($controller, $methodName, \Exception $exception): RedirectResponse{
        if($exception instanceof NotGeneralManagerException){
            var_dump('coucou');
            $route = 'workspace.page.errorAccess';
            $params = [];
            
            $url = $this->urlGenerator->linkToRoute($route, $params);

            return new RedirectResponse($url);

        }
    }

}