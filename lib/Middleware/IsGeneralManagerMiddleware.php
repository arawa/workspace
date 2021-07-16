<?php

namespace OCA\Workspace\Middleware;

use Exception;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\AppFramework\Middleware;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\IControllerMethodReflector;
use OCA\Workspace\Middleware\Exceptions\AccessDeniedException;

class IsGeneralManagerMiddleware extends Middleware {

    /** @var IControllerMethodReflector */
    private $reflector;

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

    public function beforeController($controller, $methodName)
    {
        if ($this->reflector->hasAnnotation('GeneralManagerRequired')) {
            if (!$this->userService->isUserGeneralAdmin()) {
                throw new AccessDeniedException();
            }
        }

        return;
    }

    public function afterException($controller, $methodName, Exception $exception)
    {
        if($exception instanceof AccessDeniedException) {
            return new JSONResponse([
                'status' => 'forbidden',
                'msg' => 'You are not allowed to perform this action'
            ], Http::STATUS_FORBIDDEN);
        }
    }

}