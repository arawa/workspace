<?php
namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\IGroupManager;
use OCP\IUserManager;

class GroupController extends Controller {

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $UserManager;

	/** @var UserService */
	private $UserService;

	public function __construct(
		IGroupManager $groupManager,
		IUserManager $userManager,
		UserService $userService
	){
		
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->userService = $userService;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Creates a group
	 *
	 * @var string $group
	 *
	 * @return @JSONResponse
	 */
	public function create($group) {
		// NB: This function could be abused by space managers to create arbitrary group. But, do we really care?
		if (!is_null($this->groupManager->get($group))) {
			return new JSONResponse(['Group ' + $group + ' already exists'], Http::STATUS_FORBIDDEN);
		}
		if (is_null($this->groupManager->createGroup($group))) {
			return new JSONResponse(['Could not create group ' + $group], Http::STATUS_FORBIDDEN);
		}
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * Adds a user to a group
	 *
	 * @var string $group
	 * @var string $user
	 *
	 * @return @JSONResponse
	 */
	public function addUser($space, $group, $user) {

		if (!$this->userService->isSpaceManagerOfSpace($space) && !$this->userService->isUserGeneralAdmin()) {
			return new JSONResponse(['You are not a manager for this space'], Http::STATUS_FORBIDDEN);
		}

		$NCGroup = $this->groupManager->get($group);
		$NCUser = $this->userManager->get($user);

		if (is_null($NCGroup)) {
			return new JSONResponse(['Group ' + $group + ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}

		$NCGroup->addUser($NCUser);

		// TODO Use Application constant 
		if (strpos($group, 'GE-') === 0) {
			$workspaceUsersGroup = $this->groupManager->get(Application::GROUP_WKSUSER);
			if (is_null($workspaceUsersGroup)) {
				$NCGroup->removeUser($NCUser);
				return new JSONResponse(['Generar error: Group ' + Application::GROUP_WKSUSER + ' does not exist'],
					Http::STATUS_EXPECTATION_FAILED);
			}

		}

		return new JSONResponse([], Http::STATUS_NO_CONTENT);
	}
}
