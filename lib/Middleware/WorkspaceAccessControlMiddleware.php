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
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;

class WorkspaceAccessControlMiddleware extends Middleware{

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

    private function isGeneralManager() {
		if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), Application::GENERAL_MANAGER)){
            return true;
		} else {
	    	return false;
		}
    }

    private function isSpaceManager() {
	// TODO: Use global constant instead of 'GE-'
        $workspaceAdminGroups = $this->groupManager->search('GE-');
        foreach($workspaceAdminGroups as $group) {
            if ($this->groupManager->isInGroup($this->userSession->getUser()->getUID(), $group->getGID())) {
                return true;
            }
        }
        return false;
    }

    public function beforeController($controller, $methodName ){

        // Checks if user is member of the General managers group
        if ($this->isGeneralManager()){
                return;
        }

        // Checks if user if member of a Space managers group
        if ($this->isSpaceManager()){
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
