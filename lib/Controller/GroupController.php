<?php
namespace OCA\Workspace\Controller;

use OCP\IGroupManager;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Controller;

class GroupController extends Controller {
	/** @var IGroupManager */
	private $groupManager;

	public function __construct(IGroupManager $groupManager){
		
		$this->groupManager = $groupManager;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * Creates a group
	 */
	public function create($group) {
		if (!is_null($this->groupManager->get($group))) {
			return new JSONResponse(['The specified group already exists'], Http::STATUS_FORBIDDEN);
		}
		if (is_null($this->groupManager->createGroup($group))) {
			return new JSONResponse(['Could not create group'], Http::STATUS_FORBIDDEN);
		}
		return new JSONResponse();
	}
}
