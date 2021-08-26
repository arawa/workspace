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
use OCA\Workspace\Service\WorkspaceService;
use OCA\Workspace\Service\UserService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;
use OCP\IGroupManager;
use OCP\ILogger;
use OCP\IUserManager;

class GroupController extends Controller {

	/** @var GroupfolderService */
	private $groupfolderService;

	/** @var IGroupManager */
	private $groupManager;

	/** @var ILogger */
	private $logger;

	/** @var IUserManager */
	private $userManager;

	/** @var UserService */
	private $userService;

	/** @var WorkspaceService */
	private $workspaceService;

	public function __construct(
		GroupfolderService $groupfolderService,
		IGroupManager $groupManager,
		ILogger $logger,
		IUserManager $userManager,
		UserService $userService,
		WorkspaceService $workspaceService
	){
		$this->groupfolderService = $groupfolderService;
		$this->groupManager = $groupManager;
		$this->logger = $logger;
		$this->userManager = $userManager;
		$this->userService = $userService;
		$this->workspaceService = $workspaceService;
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
		$space = $this->workspaceService->get($spaceId);
		$json = $this->groupfolderService->addGroup($space['groupfolder_id'], $gid);
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
		// TODO Use groupfolder api to retrieve workspace group. 
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
	 * @var string $gid ID of the group to be renamed
	 * @var string $newGroupName The group's new name
	 * @var string $spaceId
	 *
	 * @return @JSONResponse
	 */
	public function rename($newGroupName, $gid, $spaceId) {
		// TODO Use groupfolder api to retrieve workspace group. 
		if (substr($gid, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(
				['You may only rename workspace groups of this space (ie: group\'s name does not end by the workspace\'s ID)'],
				Http::STATUS_FORBIDDEN
			);
		}
		if (substr($newGroupName, -strlen($spaceId)) != $spaceId) {
			return new JSONResponse(
				['Workspace groups must ends with the ID of the space they belong to'],
				Http::STATUS_FORBIDDEN
			);
		}

		// Rename group
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			return new JSONResponse(['Group ' . $gid . ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}
		$NCGroup->setDisplayName($newGroupName);

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 *
	 * Adds a user to a group.
	 * The function automaticaly adds the user the the corresponding workspace's user group, and to the application
	 * manager group when we are adding a workspace manager
	 *
	 * @var string $gid
	 * @var string $user
	 *
	 * @return @JSONResponse
	 */
	public function addUser($spaceId, $gid, $user) {

		// Makes sure group exist
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			// In some cases, frontend might give a group's displayName rather than its gid
			$NCGroup = $this->groupManager->search($gid);
			if (empty($NCGroup)) {
				return new JSONResponse(['Group ' + $gid + ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
			}
			$NCGroup = $NCGroup[0];
		}

		// Adds user to group
		$NCUser = $this->userManager->get($user);
		$NCGroup->addUser($NCUser);
		
		// Adds the user to the application manager group when we are adding a workspace manager
		if (strpos($gid, Application::GID_SPACE . Application::ESPACE_MANAGER_01. $spaceId) === 0) {
			$workspaceUsersGroup = $this->groupManager->get(Application::GROUP_WKSUSER);
			if (!is_null($workspaceUsersGroup)) {
				$workspaceUsersGroup->addUser($NCUser);
			} else {
				$NCGroup->removeUser($NCUser);
				return new JSONResponse(['Generar error: Group ' . Application::GROUP_WKSUSER . ' does not exist'],
					Http::STATUS_EXPECTATION_FAILED);
			}

		}

		// Adds user to workspace user group
		// This must be the last action done, when all other previous actions have succeeded
		$space = $this->workspaceService->get($spaceId);
		$UGroup = $this->groupManager->search(Application::GID_SPACE .Application::ESPACE_USERS_01 . $spaceId)[0];
		$UGroup->addUser($NCUser);

		return new JSONResponse(['message' => 'The user ' . $user . ' is added in the ' . $gid . ' group'], Http::STATUS_NO_CONTENT);

	}

	/**
	 * @NoAdminRequired
	 * @SpaceAdminRequired
	 * @NoCSRFRequired
	 * 
	 * Removes a user from a group
	 * The function also remove the user from all workspace 'subgroup when the user is being removed from the U- group
	 * and from the WorkspacesManagers group when the user is being removed from the GE- group
	 *
	 * @var string $gid
	 * @var string $user
	 *
	 * @return @JSONResponse
	 */
	public function removeUser($spaceId, $gid, $user) {

		$this->logger->debug('Removing user ' . $user . ' from group ' . $gid);

		// Makes sure group exist
		$NCGroup = $this->groupManager->get($gid);
		if (is_null($NCGroup)) {
			$this->logger->error('Group ' . $gid . ' does not exist');
			return new JSONResponse(['Group ' . $gid . ' does not exist'], Http::STATUS_EXPECTATION_FAILED);
		}
		$groups = [];

		// Removes user from group
		$NCUser = $this->userManager->get($user);
		// Special cases when we are removing user from a U- or GE group
		// TODO: Update the front-end to suit the number of the groups deleted.
		if ($gid === Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $spaceId
		|| $gid === Application::GID_SPACE . Application::ESPACE_USERS_01 . $spaceId) {
			// Removes user from all 'subgroups' when we remove it from the workspace's user group
			$this->logger->debug('Removing user from a workspace, removing it from all the workspace subgroups too.');
			$space = $this->workspaceService->get($spaceId);
			$users = (array)$space['users'];
			$groupsFromUser = $users[$NCUser->getUID()]['groups'];
			foreach($groupsFromUser as $groupId) {
				$NCGroup = $this->groupManager->get($groupId);
				$NCGroup->removeUser($NCUser);
				$groups[] = $NCGroup->getGID();
				$this->logger->debug('User removed from group: ' . $NCGroup->getDisplayName());
				if ($groupId === Application::GID_SPACE . Application::ESPACE_MANAGER_01 . $spaceId) {
					$this->logger->debug('Removing user from a workspace manager group, removing it from the WorkspacesManagers group if needed.');
					$this->userService->removeGEFromWM($NCUser, $spaceId);
				}
			}
			return new JSONResponse([
				'statuscode' => Http::STATUS_NO_CONTENT,
				'user' => $NCUser->getUID(),
				'groups' => $groups
			]);	
		}
		// delete subgroup only
		$groups[] = $NCGroup->getGID();
		$NCGroup->removeUser($NCUser);
		return new JSONResponse([
			'statuscode' => Http::STATUS_NO_CONTENT,
			'user' => $NCUser->getUID(),
			'groups' => $groups
		]);
	}

}
