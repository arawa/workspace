<?php

namespace OCA\Workspace\Controller;

use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;

class GeneralManagerController extends Controller {

    public function __construct($AppName, IRequest $request)
    {
        parent::__construct($AppName, $request);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     * 
     * TODO: Write the code to update the general manager group.
     * 
     * @return JSONResponse
     */
    public function updateGeneralManagerGroup() {
        // code
    }
}