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
use OCA\Workspace\Files\InternalFile;
use OCA\Workspace\Files\LocalFile;
use OCA\Workspace\Service\UserService;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Users\UsersExistCheck;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

/**
 * @todo rename to import csv users : ImportCsvUsersController
*/
class FileCSVController extends Controller {
	private $currentUser;
	
	public function __construct(
		string $appName,
		IRequest $request,
		private IUserManager $userManager,
		private WorkspaceService $workspaceService,
		private UserService $userService,
        private UsersExistCheck $userChecker,
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

		if ($this->isCSVMimeType($file)) {
			return new JSONResponse(
				[
					'Wrong file extension. Must be <b>.csv</b>.'
				],
				Http::STATUS_FORBIDDEN
			);
		}

		$csv = new Csv();

		$localFile = new LocalFile($file['tmp_name']);

		if (!$csv->hasProperHeader($localFile)) {
			return new JSONResponse(
				[
					'Invalid file format. Table header doesn\'t contain any of the following values:<br>',
					[
						...$csv::DISPLAY_NAME,
						...$csv::ROLE
					]
				],
				Http::STATUS_FORBIDDEN
			);
		} else {
			new JSONResponse(
				[
					'Something went wrong. Couldn\'t open a file.'
				],
				Http::STATUS_FORBIDDEN
			);
		}

		$names = $csv->parser($localFile);
		
        $usernames = array_map(fn($user) => $user['name'], $names);
        if (!$this->userChecker->checkUsersExist($usernames))
        {
            $errorMessage = 'Users doesn\'t exist in your csv file.<br>';
            $usersErrorInTheName = array_filter(
                $usernames,
                function ($name) {
                    return !$this->userChecker->checkUserExist($name);
                }
            );
            $usersErrorInTheName = array_map(
                fn($name) => "- $name",
                $usersErrorInTheName
            );
            $usersErrorInTheName = implode("<br>", $usersErrorInTheName);
            $errorMessage .= 'Please, check these users in your csv file :<br>';
            $errorMessage .= $usersErrorInTheName;
            return new JSONResponse([$errorMessage], Http::STATUS_FORBIDDEN);
        }

		$existingNames = array_filter($names, function ($user) {
			return $this->userManager->userExists($user['name']);
		});
		
		// get list of IUser objects
		$users = $this->formatUsers($existingNames);

		$data = $this->formatData($users, $space);

		return new JSONResponse($data);
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 * Returns formatted list of existing users of the instance.
	 *
	 */
	public function getFromFiles():JSONResponse {
		$params = $this->request->getParams();
		$path = $params['path'];
		$spaceObj = $params['space'];
		$space = json_decode($spaceObj, true);
		$uid = $this->currentUser->getUID();
		$folder = $this->rootFolder->getUserFolder($uid);
		$file = $folder->get($path);

		if ($this->isCSVMimeType($file)) {
			return new JSONResponse(['Wrong file extension. Must be <b>.csv</b>.'], Http::STATUS_FORBIDDEN);
		}

		$fullPath = $file->getInternalPath();
		$csv = new Csv();
		$internalFile = new InternalFile($fullPath, $file->getStorage());

		if (!$csv->hasProperHeader($internalFile)) {
			return new JSONResponse(['Invalid file format. Table header doesn\'t contain any of the following values:<br>', [...$csv::DISPLAY_NAME, ...$csv::ROLE]], Http::STATUS_FORBIDDEN);
		}

		$names = $csv->parser($internalFile);

        $usernames = array_map(fn($user) => $user['name'], $names);
        if (!$this->userChecker->checkUsersExist($usernames))
        {
            $errorMessage = 'Users doesn\'t exist in your csv file.<br>';
            $usersErrorInTheName = array_filter(
                $usernames,
                function ($name) {
                    return !$this->userChecker->checkUserExist($name);
                }
            );
            $usersErrorInTheName = array_map(
                fn($name) => "- $name",
                $usersErrorInTheName
            );
            $usersErrorInTheName = implode("<br>", $usersErrorInTheName);
            $errorMessage .= 'Please, check these users in your csv file :<br>';
            $errorMessage .= $usersErrorInTheName;
            return new JSONResponse([$errorMessage], Http::STATUS_FORBIDDEN);
        }

		// filter array to leave only existing users
		$existingNames = array_filter($names, function ($user) {
			return $this->userManager->userExists($user['name']);
		});

		$users = $this->formatUsers($existingNames);

		$data = $this->formatData($users, $space);

		return new JSONResponse($data);
	}

	private function formatData(array $users, array $space): array {
		$data = [];
		foreach ($users as $user) {
			$role = $user['role'] == "admin" ? "admin" : "user";
			$data[] = $this->userService->formatUser($user['user'], $space, $role);
		}

		return $data;
	}

	private function formatUsers(array $data): array {
		$users = [];
		foreach($data as $user) {
			$users[] = [
				'user' => $this->userManager->get($user['name']),
				'role' => $user['role']
			];
		}
		return $users;
	}

	private function isCSVMimeType(Node|array $file): bool {
		if($file instanceof Node) {
			return $file->getMimetype() !== 'text/csv';
		}

		return $file['type'] !== 'text/csv';
	}
}
