<?php

namespace OCA\Workspace\Controller;

use OCA\Workspace\Db\OCFileCacheMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

class WorkspacefichierController extends Controller {

    private OCFileCacheMapper $fileCache;
    
    public function __construct(
        $AppName,
        IRequest $request,
        OCFileCacheMapper $fileCache
    ) {
        parent::__construct($AppName, $request);
        $this->fileCache = $fileCache;
    }
    

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getGroupFolderId($fileId = ""): Response {
        
        $result = $this->fileCache->getGroupFolderId((int)$fileId);
        // var_dump("coucou");
        // $result = "tagada";
        return new JSONResponse([
            'result' => $result
        ]);
    }

    /**
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function getTest() {
        return new JSONResponse([ 'test' => 'test']);
    }
}
