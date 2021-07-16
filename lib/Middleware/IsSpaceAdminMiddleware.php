<?php
/**
 *
 * @author Cyrille Bollu <cyrille@bollu.be>
 *
 * IsSpaceAdminMiddleware: This middleware ensures that controller
 * methods with the @SpaceAdminRequired annotation run only if the
 * user is manager of the corresponding workspace or an application
 * manager.
 *
 * TODO: Add licence
 *
 */

namespace OCA\Workspace\Middleware;

use OCA\Workspace\Service\UserService;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Middleware;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCP\IRequest;

class IsSpaceAdminMiddleware extends Middleware{

    /** @var IControllerMethodReflector */
    private $reflector;

    /** @var IUserSession */
    private $userSession;

    /** @var UserService */
    private $userService;

    public function __construct(
	IControllerMethodReflector $reflector,
	IRequest $request,
	UserService $userService
    )
    {
        $this->reflector = $reflector;
        $this->request = $request;
        $this->userService = $userService;
    }

    public function beforeController($controller, $methodName ){

        if ($this->reflector->hasAnnotation('SpaceAdminRequired')) {
            $spaceId = $this->request->getParam('spaceId');
            if (!$this->userService->isSpaceManagerOfSpace($spaceId) && !$this->userService->isUserGeneralAdmin()){
                throw new AccessDeniedException();
            }
        }

        return;

    }

    public function afterException($controller, $methodName, \Exception $exception){
        if($exception instanceof AccessDeniedException){
            return new JSONResponse([
                'status' => 'forbidden',
                'msg' => 'You are not allowed to perform this action.'
            ], Http::STATUS_FORBIDDEN);
        }
    }

}
