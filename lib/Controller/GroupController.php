<?php
/**
 *
 * @author Cyrille Bollu <cyrille@bollu.be>
 * @author Baptiste Fotia <baptiste.fotia@arawa.fr>
 *
 * TODO: Add licence
 *
 */

namespace OCA\Workspace\Controller;

use OCA\Workspace\AppInfo\Application;
use OCA\Workspace\Service\GroupfolderService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\IGroupManager;
use OCP\IUserManager;

class GroupController extends Controller {

	/** @var GroupfolderService */
	private $groupfolderService;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $UserManager;

	/** @var UserService */
	private $UserService;

	public function __construct(
		GroupfolderService $groupfolderService,
		IGroupManager $groupManager,
		IUserManager $userManager,
		UserService $userService
	){
		$this->groupfolderService = $groupfolderService;
		$this->groupManager = $groupManager;
		$this->userManager = $userManager;
		$this->userService = $userService;
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Creates a group
	 * NB: This function could probably be abused by space managers to create arbitrary group. But, do we really care?
	 *
	 * @var string $gid
	 * @var string $spaceId
	 *
	 * @return @JSONResponse
	 */
	public function create($gid, $spaceId) {
		if (!is_null($this->groupManager->get($gid))) {
			return new JSONResponse(['Group ' + $gid + ' already exists'], Http::STATUS_FORBIDDEN);
		}

		// Creates group
		$NCGroup = $this->groupManager->createGroup($gid);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Could not create group ' + $gid], Http::STATUS_FORBIDDEN);
		}

		// Grants group access to groupfolder
		$json = $this->groupfolderService->addGroup($spaceId, $gid);
		$resp = json_decode($json->getBody(), true);
		if ($resp['ocs']['meta']['statuscode'] !== 100) {
			$NCGroup->delete();
			return new JSONResponse(['Could not assign group to groupfolder. Group has not been created.'], Http::STATUS_FORBIDDEN);
		}

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Deletes a group
	 * Cannot delete GE- and U- groups (This is on-purpose)
	 *
	 * @var string $gid
	 * @var string $spaceId
	 *
	 * @return @JSONResponse
	 */
	public function delete($gid, $spaceId) {
		if (substr($gid, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(['You may only delete workspace groups of this space (ie: group\'s name does not end by the workspace\'s ID)'], Http::STATUS_FORBIDDEN);
		}

		// Delete group
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Group ' + $gid + ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}
		$NCGroup->delete();

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Renames a group
	 * Cannot rename GE- and U- groups (This is on-purpose)
	 *
	 * @var string $oldGroup The group to be renamed
	 * @var string $newGroup The group's new name
	 * @var string $spaceId
	 *
	 * @return @JSONResponse
	 */
	public function rename($newGroup, $oldGroup, $spaceId) {
		if (substr($oldGroup, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(
				['You may only rename workspace groups of this space (ie: group\'s name does not end by the workspace\'s ID)'],
				Http::STATUS_FORBIDDEN
			);
		}
		if (substr($newGroup, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(
				['Workspace groups must ends with the ID of the space they belong to'],
				Http::STATUS_FORBIDDEN
			);
		}

		// Rename group
		$NCGroup = $this->groupManager->get($oldGroup);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Group ' + $oldGroup + ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}
		$NCGroup->setDisplayName($newGroup);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Adds a user to a group
	 *
	 * @var string $group
	 * @var string $user
	 *
	 * @return @JSONResponse
	 */
	public function addUser($spaceId, $group, $user) {

		$NCGroup = $this->groupManager->get($group);
		$NCUser = $this->userManager->get($user);

		if (is_null($NCGroup)) {
			return new JSONResponse(['Group ' + $group + ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}

		$NCGroup->addUser($NCUser);

		if (strpos($group, Application::ESPACE_MANAGER_01) === 0) {
			$workspaceUsersGroup = $this->groupManager->get(Application::GROUP_WKSUSER);
			if (is_null($workspaceUsersGroup)) {
				$NCGroup->removeUser($NCUser);
				return new JSONResponse(['Generar error: Group ' + Application::GROUP_WKSUSER + ' does not exist'],
					Http::STATUS_EXPECTATION_FAILED);
			}

		}
		return new JSONResponse(['message' => 'The user '. $user .' is added in the '. $group .' group'], Http::STATUS_NO_CONTENT);

	}
}
