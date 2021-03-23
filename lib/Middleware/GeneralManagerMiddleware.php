<?php

namespace OCA\Workspace\Middleware;

use OCP\AppFramework\Middleware;
use OCP\IGroupManager;
use OCP\IUser;

class GeneralManagerMiddleware extends Middleware{

    private const GENERAL_MANAGER = "GeneralManager";

    private $group;

    // public function __construct(IGroupManager $group)
    // {
    //     $this->group = $group;
    // }

    public function beforeController($controller, $methodName, IUser $userId ){

        var_dump("coucou");

    }
}