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

use OCA\Workspace\Exceptions\Notifications\AbstractNotificationException;
use OCA\Workspace\Exceptions\Notifications\BadMimeType;
use OCA\Workspace\Exceptions\Notifications\InvalidCsvFormatException;
use OCA\Workspace\Exceptions\Notifications\InvalidSeparatorCsvException;
use OCA\Workspace\Exceptions\Notifications\UserDoesntExistException;
use OCA\Workspace\Files\Csv\CheckMimeType;
use OCA\Workspace\Files\Csv\ImportUsers\Header;
use OCA\Workspace\Files\Csv\ImportUsers\HeaderValidator;
use OCA\Workspace\Files\Csv\ImportUsers\Parser;
use OCA\Workspace\Files\Csv\ImportUsers\Values;
use OCA\Workspace\Files\Csv\ImportUsers\ValuesValidator;
use OCA\Workspace\Files\Csv\SeparatorDetector;
use OCA\Workspace\Files\Csv\StructureValidator;
use OCA\Workspace\Files\FileUploader;
use OCA\Workspace\Files\NextcloudFile;
use OCA\Workspace\Notifications\ToastMessager;
use OCA\Workspace\Response\ErrorResponseFormatter;
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Users\UserFormatter;
use OCA\Workspace\Users\UsersExistCheck;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Files\IRootFolder;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;

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
		private IL10N $translate,
		private IRootFolder $rootFolder,
		private ValuesValidator $valuesValidator
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
		try {
			$params = $this->request->getParams();
			$spaceObj = $params['space'];
			$space = json_decode($spaceObj, true);
			$file = $this->request->getUploadedFile('file');
	
			if ($this->csvCheckMimeType->checkOnArray($file)) {
				throw new BadMimeType(
					$this->translate->t('Error in file format'),
					$this->translate->t('The file must be in <b>CSV format</b>.'),
				);
			}
	
			$fileUploader = new FileUploader($file['tmp_name']);
				
			if (!SeparatorDetector::isComma($fileUploader) || !SeparatorDetector::isCommaForAllFile($fileUploader)) {
				throw new InvalidSeparatorCsvException(
					$this->translate->t('Invalid separator for CSV file'),
					$this->translate->t('Your CSV file must use a comma (",") as separator'),
				);
			}

			if (!StructureValidator::checkCommaAllLines($fileUploader))
			{
				throw new InvalidSeparatorCsvException(
					$this->translate->t('Invalid separator for CSV file'),
					$this->translate->t('Your CSV file must use a comma (",") as separator'),
				);
			}
	
			if (!$this->headerValidator->validate($fileUploader)) {

				$displaynamesBold = array_map(fn ($displayname) => "<b>$displayname</b>", Header::DISPLAY_NAME);
				$rolesBold = array_map(fn ($role) => "<b>$role</b>", Header::ROLE);
	
				$separatorOr = $this->translate->t('or');
				$displaynamesBoldStringify = implode(" $separatorOr ", $displaynamesBold);
				$rolesBoldStringify = implode(" $separatorOr ", $rolesBold);
	
				$message = "The content of your file is invalid. "
				. "Header does not contain the desired values. "
				. "Two columns are required, with the following header names and values:<br>"
				."- \"user\": the user's username or e-mail address<br>"
				. "- \"role\": the user's role (\"u\" or \"user\" for a user and \"wm\" for a workspace manager)";
	
				$errorMessage = $this->translate->t(
					$message,
					[
						$displaynamesBoldStringify,
						$rolesBoldStringify
					]
				);

				throw new InvalidCsvFormatException(
					$this->translate->t('Error in CSV file content'),
					$this->translate->t($errorMessage),
				);
			}
			
			$usersFormatted = $this->csvParser->parser($fileUploader);
			
			$uids = array_map(fn ($user) => $user->uid, $usersFormatted);

			$emails = array_values(
				array_filter(
					$uids,
					fn ($uid) => filter_var($uid, FILTER_VALIDATE_EMAIL)
				)
			);

			$usernames = array_values(
				array_diff($uids, $emails)
			);

			if (!$this->userChecker->checkUsersExist($usernames)
				|| !$this->userChecker->checkUsersExistByEmail($emails)) {
				$usernamesUnknown = array_filter(
					$usernames,
					function ($name) {
						return !$this->userChecker->checkUserExist($name);
					}
				);

				$emailsUnknown = array_filter(
					$emails,
					fn ($email) => !$this->userChecker->checkUserExistByEmail($email)
				);

				$usersUnknown = array_merge($usernamesUnknown, $emailsUnknown);

				$usersUnknown = array_map(
					fn ($name) => "- $name",
					$usersUnknown
				);
				$usersUnknown = implode("<br>", $usersUnknown);
				$errorMessage = $this->translate->t('The users of this CSV file are unknown and can not be imported. Check the following users and repeat the process:<br>');
				$errorMessage .= $usersUnknown;
				throw new UserDoesntExistException(
					$this->translate->t('Error: unknown users'),
					$errorMessage,
					Http::STATUS_FORBIDDEN
				);
			}

			if (!$this->valuesValidator->validateRoles($fileUploader)) {

				$usersBadRole = array_filter(
					$usersFormatted,
					fn ($user) => !in_array($user->role, Values::ROLES)
				);

				$message = 'Only the following values are allowed: <b>%1$s</b><br><br>'
				. '- "wm": to define the user as a workspace manager.<br>'
				. '- "u" or "user": to define the user as a simple user.<br><br>'
				. 'Check the role for these users:<br>%2$s';

				$usersBadRoleStringify = implode(
					'<br>',
					array_map(
						fn ($user) => $this->translate->t('- <b>%1$s</b> has the <b>%2$s</b> role', [$user->uid, $user->role]),
						$usersBadRole
					)
				);

				throw new InvalidCsvFormatException(
					$this->translate->t('Error: unknown role'),
					$this->translate->t($message, [
						implode(', ', Values::ROLES),
						$usersBadRoleStringify
					])
				);
			}
	
			$data = [];
			foreach($usersFormatted as $user) {
				$uid = $user->uid;

				if ($this->userChecker->checkUserExistByEmail($uid)) {
					$uid = $this->userManager->getByEmail($user->uid)[0];
				} else {
					$uid = $this->userManager->get($user->uid);
				}

				$data[] = [
					'user' => $uid,
					'role' => $user->role
				];
			}
	
			$data = array_map(
				fn ($user) => $this->userFormatter->formatUser($user['user'], $space, $user['role']),
				$data
			);
	
			return new JSONResponse($data);
		} catch(AbstractNotificationException $exception) {
			return new JSONResponse(
				ErrorResponseFormatter::format(
					new ToastMessager($exception->getTitle(), $exception->getMessage()),
					$exception
				),
				$exception->getCode()
			);
		} catch(\Exception $exception) {
			return new JSONResponse(
				ErrorResponseFormatter::format(
					new ToastMessager($this->translate->t('Unknown error'), $exception->getMessage()),
					$exception
				),
				$exception->getCode()
			);
		}
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 * Returns formatted list of existing users of the instance.
	 *
	 */
	public function getFromFiles():JSONResponse {
		try {
			$params = $this->request->getParams();
			$path = $params['path'];
			$spaceObj = $params['space'];
			$space = json_decode($spaceObj, true);
			$uid = $this->currentUser->getUID();
			$folder = $this->rootFolder->getUserFolder($uid);
			$file = $folder->get($path);
	
			if ($this->csvCheckMimeType->checkOnNode($file)) {
				throw new BadMimeType(
					$this->translate->t('Error in file format'),
					$this->translate->t('The file must be in <b>CSV format</b>.'),
				);
			}
		
			$nextcloudFile = new NextcloudFile($file);
	
			if (!SeparatorDetector::isComma($nextcloudFile) || !SeparatorDetector::isCommaForAllFile($nextcloudFile)) {
				throw new InvalidSeparatorCsvException(
					$this->translate->t('Invalid separator for CSV file'),
					$this->translate->t('Your CSV file must use a comma (",") as separator'),
				);
			}

			if (!StructureValidator::checkCommaAllLines($nextcloudFile))
			{
				throw new InvalidSeparatorCsvException(
					$this->translate->t('Invalid separator for CSV file'),
					$this->translate->t('Your CSV file must use a comma (",") as separator'),
				);
			}
	
			if (!$this->headerValidator->validate($nextcloudFile)) {
				$displaynamesBold = array_map(fn ($displayname) => "<b>$displayname</b>", Header::DISPLAY_NAME);
				$rolesBold = array_map(fn ($role) => "<b>$role</b>", Header::ROLE);
	
				$separatorOr = $this->translate->t('or');
				$displaynamesBoldStringify = implode(" $separatorOr ", $displaynamesBold);
				$rolesBoldStringify = implode(" $separatorOr ", $rolesBold);

				$message = "The content of your file is invalid. "
				. "Header does not contain the desired values. "
				. "Two columns are required, with the following header names and values:<br>"
				."- \"user\": the user's username or e-mail address<br>"
				. "- \"role\": the user's role (\"u\" or \"user\" for a user and \"wm\" for a workspace manager)";
	
				$errorMessage = $this->translate->t(
					$message,
					[
						$displaynamesBoldStringify,
						$rolesBoldStringify
					]
				);

				throw new InvalidCsvFormatException(
					$this->translate->t('Error in CSV file content'),
					$this->translate->t($errorMessage),
				);
			}

			$names = $this->csvParser->parser($nextcloudFile);
	
			$usernames = array_map(fn ($user) => $user->uid, $names);

			$uids = array_map(fn ($user) => $user->uid, $names);

			$emails = array_values(
				array_filter(
					$uids,
					fn ($uid) => filter_var($uid, FILTER_VALIDATE_EMAIL)
				)
			);

			$usernames = array_values(
				array_diff($uids, $emails)
			);

			if (!$this->userChecker->checkUsersExist($usernames)
			|| !$this->userChecker->checkUsersExistByEmail($emails)) {

				$usernamesUnknown = array_filter(
					$usernames,
					function ($name) {
						return !$this->userChecker->checkUserExist($name);
					}
				);

				$emailsUnknown = array_filter(
					$emails,
					fn ($email) => !$this->userChecker->checkUserExistByEmail($email)
				);

				$usersUnknown = array_merge($usernamesUnknown, $emailsUnknown);

				$usersUnknown = array_map(
					fn ($name) => "- $name",
					$usersUnknown
				);
				$usersUnknown = implode("<br>", $usersUnknown);
				$errorMessage = $this->translate->t('The users of this CSV file are unknown and can not be imported. Check the following users and repeat the process:<br>');
				$errorMessage .= $usersUnknown;
				throw new UserDoesntExistException(
					$this->translate->t('Error: unknown users'),
					$errorMessage,
					Http::STATUS_FORBIDDEN
				);
			}
	
			if (!$this->valuesValidator->validateRoles($nextcloudFile)) {

				$usersBadRole = array_filter(
					$names,
					fn ($user) => !in_array($user->role, Values::ROLES)
				);

				$message = 'Only the following values are allowed: <b>%1$s</b><br><br>'
				. '- "wm": to define the user as a workspace manager.<br>'
				. '- "u" or "user": to define the user as a simple user.<br><br>'
				. 'Check the role for these users:<br>%2$s';

				$usersBadRoleStringify = implode(
					'<br>',
					array_map(
						fn ($user) => $this->translate->t('- <b>%1$s</b> has the <b>%2$s</b> role', [$user->uid, $user->role]),
						$usersBadRole
					)
				);

				throw new InvalidCsvFormatException(
					$this->translate->t('Error: unknown role'),
					$this->translate->t($message, [
						implode(', ', Values::ROLES),
						$usersBadRoleStringify
					])
				);
			}

			$data = [];
			foreach($names as $user) {
				$uid = $user->uid;

				if ($this->userChecker->checkUserExistByEmail($uid)) {
					$uid = $this->userManager->getByEmail($user->uid)[0];
				} else {
					$uid = $this->userManager->get($user->uid);
				}

				$data[] = [
					'user' => $uid,
					'role' => $user->role
				];
			}
	
			$data = array_map(
				fn ($user) => $this->userFormatter->formatUser($user['user'], $space, $user['role']),
				$data
			);
	
			return new JSONResponse($data);
		} catch(AbstractNotificationException $exception) {
			return new JSONResponse(
				ErrorResponseFormatter::format(
					new ToastMessager($exception->getTitle(), $exception->getMessage()),
					$exception
				),
				$exception->getCode()
			);
		} catch(\Exception $exception) {
			return new JSONResponse(
				ErrorResponseFormatter::format(
					new ToastMessager($this->translate->t('Unknown error'), $exception->getMessage()),
					$exception
				),
				$exception->getCode()
			);
		}
	}
}
