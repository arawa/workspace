<?php

/**
 * @copyright Copyright (c) 2017 Arawa
 *
 * @author 2021 Baptiste Fotia <baptiste.fotia@arawa.fr>
 * @author 2021 Cyrille Bollu <cyrille@bollu.be>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Workspace\Controller;

use OCA\Workspace\Files\Csv;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\AppFramework\Http;
use OCP\IUserManager;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Service\UserService;

class FileCSVController extends Controller {

    public function __construct(
        string $appName,
        IRequest $request,
        private IUserManager $userManager,
        private WorkspaceService $workspaceService,
        private UserService $userService,
    ) {
        parent::__construct($appName, $request);
    }

    /**
     * @NoAdminRequired
     * @SpaceAdminRequired
     * Gets list of users from csv file.
     * @param string $gid
     * @param array|string $space
     * 
     * @return JSONResponse
	 *
	 */
    public function import(): JSONResponse {
        $params = $this->request->getParams();
        $parser = new Csv();
        $spaceObj = $params['space'];
        $space = json_decode($spaceObj, true);
        $file = $this->request->getUploadedFile('file');
        // verify that file has csv format
        if ($file['type'] !== 'text/csv') {
            return new JSONResponse(['Invalid file extension - ' . $file['type']], Http::STATUS_FORBIDDEN);
        }
        $names = $parser->parser($file);
        // filter array to leave only existing users
        $existingNames = array_filter($names, function($user) {
            return $this->userManager->userExists($user['name']);
            
        });
        // get list of IUser objects
        $users = [];
        foreach( $existingNames as $user) {
            $users[] = $this->userManager->get($user['name']);
        }
        $data = [];
        foreach ($users as $user) {
            $data[] = $this->userService->formatUser($user, $space, 'user');
        }
        return new JSONResponse($data);
    }
}
