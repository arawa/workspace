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

use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\AppFramework\Http;
use OCP\Files\IRootFolder;
use OCP\AppFramework\Controller;
use OCA\Workspace\Files\FileUploader;
use OCA\Workspace\Files\NextcloudFile;
use OCA\Workspace\Users\UserFormatter;
use OCP\AppFramework\Http\JSONResponse;
use OCA\Workspace\Users\UsersExistCheck;
use OCA\Workspace\Files\Csv\CheckMimeType;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Files\Csv\ImportUsers\Header;
use OCA\Workspace\Files\Csv\ImportUsers\Parser;
use OCA\Workspace\Files\Csv\ImportUsers\HeaderValidator;
use OCA\Workspace\Files\Csv\SeparatorDetector;

/**
 * @todo rename to import csv users : ImportCsvUsersUploaderController
*/
class FileCSVController extends Controller {
	private $currentUser;
	
	public function __construct(
		string $appName,
		IRequest $request,
		private IUserManager $userManager,
		private WorkspaceService $workspaceService,
        private UserFormatter $userFormatter,
        private UsersExistCheck $userChecker,
        private CheckMimeType $csvCheckMimeType,
        private HeaderValidator $headerValidator,
        private Parser $csvParser,
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

		if ($this->csvCheckMimeType->checkOnArray($file)) {
			return new JSONResponse(
				[
					'Wrong file extension. Must be <b>.csv</b>.'
				],
				Http::STATUS_FORBIDDEN
			);
		}

		$fileUploader = new FileUploader($file['tmp_name']);

        if (!SeparatorDetector::isComma($fileUploader)) {
            return new JSONResponse(
                [
                    'Your csv file should be a comma (",") as separator',
                ],
                Http::STATUS_FORBIDDEN
			);
        }

		if (!$this->headerValidator->validate($fileUploader)) {
			return new JSONResponse(
				[
					'Invalid file format. Table header doesn\'t contain any of the following values:<br>',
					[
						...Header::DISPLAY_NAME,
						...Header::ROLE
					]
				],
				Http::STATUS_FORBIDDEN
			);
		}

		$names = $this->csvParser->parser($fileUploader);
		
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

        $names = array_map(function($user) {
            $user['user'] = $this->userManager->get($user['name']);
            return $user;
        }, $names);

        $data = array_map(
            fn($user) => $this->userFormatter->formatUser($user['user'], $space, $user['role']),
            $names
        );

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

		if ($this->csvCheckMimeType->checkOnNode($file)) {
			return new JSONResponse(['Wrong file extension. Must be <b>.csv</b>.'], Http::STATUS_FORBIDDEN);
		}

		$fullPath = $file->getInternalPath();

        $nextcloudFile = new NextcloudFile($fullPath, $file->getStorage());

        if (!SeparatorDetector::isComma($nextcloudFile)) {
            return new JSONResponse(
                [
                    'Your csv file should be a comma (",") as separator',
                ],
                Http::STATUS_FORBIDDEN
			);
        }

		if (!$this->headerValidator->validate($nextcloudFile)) {
			return new JSONResponse(['Invalid file format. Table header doesn\'t contain any of the following values:<br>', [...Header::DISPLAY_NAME, ...Header::ROLE]], Http::STATUS_FORBIDDEN);
		}

		$names = $this->csvParser->parser($nextcloudFile);

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

        $names = array_map(function($user) {
            $user['user'] = $this->userManager->get($user['name']);
            return $user;
        }, $names);

        $data = array_map(
            fn($user) => $this->userFormatter->formatUser($user['user'], $space, $user['role']),
            $names
        );

		return new JSONResponse($data);
	}
}
