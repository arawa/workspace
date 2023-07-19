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

use Exception;
use OCA\Workspace\Files\Csv;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\WorkspaceService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUser;
use OCP\Files\Storage\IStorage;
use OCP\IUserSession;

class FileCSVController extends Controller {
	private $currentUser;
	private IStorage $storage;
	
	public function __construct(
		string $appName,
		IRequest $request,
		private IUserManager $userManager,
		private WorkspaceService $workspaceService,
		private UserService $userService,
		// private FileInfo $file,
		private IUserSession $userSession,
		private IRootFolder $rootFolder,
		
	
	) {
		parent::__construct($appName, $request);
		$this->currentUser = $userSession->getUser();
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 * Returns formatted list of existing users of the instance.
	 *
	 */
	public function import(): JSONResponse {
		$params = $this->request->getParams();
		$spaceObj = $params['space'];
		$space = json_decode($spaceObj, true);
		$file = $this->request->getUploadedFile('file');
		if ($file['type'] !== 'text/csv') {
			return new JSONResponse(['Wrong file extension. Must be <b>.csv</b>.'], Http::STATUS_FORBIDDEN);
		}
		$csv = new Csv();
		if (($handler = fopen($file['tmp_name'], "r")) !== false) {
		    if (!$csv->hasProperHeader($handler)) {
		        return new JSONResponse(['Invalid file format. Table header doesn\'t contain any of the following values:<br>', [...$csv::DISPLAY_NAME, ...$csv::ROLE]], Http::STATUS_FORBIDDEN);
		    }
            $names = $csv->parser($handler);
            fclose($handler);

		} else {
		    return new JSONResponse(['Something went wrong. Couldn\'t open a file.'], Http::STATUS_FORBIDDEN);
		}
		$existingNames = array_filter($names, function ($user) {
			return $this->userManager->userExists($user['name']);	
		});
		// get list of IUser objects
		$users = [];
		foreach($existingNames as $user) {
			$users[] = [$this->userManager->get($user['name']), $user['role']];
		}
		$data = [];
		foreach ($users as $user) {
			$role = $user[1] == "admin" ? "admin" : "user";
			$data[] = $this->userService->formatUser($user[0], $space, $role);
		}
		return new JSONResponse($data);
	}

	public function getFromFiles():JSONResponse {
		$params = $this->request->getParams();
		$path = $params['path'];
		$spaceObj = $params['space'];
		$space = json_decode($spaceObj, true);
		$uid = $this->currentUser->getUID();
		$folder = $this->rootFolder->getUserFolder($uid);
		$file = $folder->get($path);
		if ($file->getMimetype() !== 'text/csv') {
			return new JSONResponse(['Wrong file extension. Must be <b>.csv</b>.'], Http::STATUS_FORBIDDEN);
		}
		$fullPath = $file->getInternalPath();
		$store = $file->getStorage();
		$csv = new Csv();
        if (($handle = $store->fopen($fullPath , "r")) !== false) {
			if (!$csv->hasProperHeader($handle)) {
				return new JSONResponse(['Invalid file format. Table header doesn\'t contain any of the following values:<br>', [...$csv::DISPLAY_NAME, ...$csv::ROLE]], Http::STATUS_FORBIDDEN);
			}
            $names = $csv->parser($handle);
            fclose($handle);
		} else {
            return new JSONResponse(['Something went wrong. Couldn\'t open a file.'], Http::STATUS_FORBIDDEN);
        }
		// filter array to leave only existing users
		$existingNames = array_filter($names, function ($user) {
			return $this->userManager->userExists($user['name']);	
		});
		// get list of IUser objects
		$users = [];
		foreach($existingNames as $user) {
			$users[] = [$this->userManager->get($user['name']), $user['role']];
		}
		$data = [];
		foreach ($users as $user) {
			$role = $user[1] == "admin" ? "admin" : "user";
			$data[] = $this->userService->formatUser($user[0], $space, $role);
		}
		return new JSONResponse($data);
	}
}
