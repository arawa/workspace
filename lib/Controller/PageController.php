<?php
namespace OCA\Workspace\Controller;

use OCP\IRequest;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

class PageController extends Controller {
	
	/** @var string */
	private $userId;

	protected $userManager;

	protected $groupManager;

	private $ESPACE_MANAGER = "GE-";

	public function __construct($AppName, IRequest $request, $UserId, IUserManager $users, IGroupManager $group){
		parent::__construct($AppName, $request);
		$this->userId = $UserId;
		$this->userManager = $users;
		$this->groupManager = $group;
	}

	/**
	 * CAUTION: the @Stuff turns off security checks; for this page no admin is
	 *          required and no CSRF check. If you don't know what CSRF is, read
	 *          it up in the docs or you might create a security hole. This is
	 *          basically the only required method to add this exemption, don't
	 *          add it to any other method if you don't exactly know what it does
	 *
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {

		$userObject = $this->userManager->get($this->userId);
		$userId = $userObject->getUID();

		$usersManager = $this->userManager->searchDisplayName('');
		
		$allUsersByEspaceManagerGroup = [];

		$allGEGroups = $this->groupManager->search($this->ESPACE_MANAGER);

		for ($i = 0; $i < count($usersManager) ; $i++ ) {
			for($j = 0; $j < count($allGEGroups); $j++){
				if( $this->groupManager->isInGroup($usersManager[$i]->getUID(), $allGEGroups[$j]->getGID()) ){
					$allUsersByEspaceManagerGroup[] = [ "uid" => $usersManager[$i]->getUID(), "email_address" => $usersManager[$i]->getEMailAddress(), "gid" => $allGEGroups[$j]->getGID() ];
				}
			}
		}
		
		return new TemplateResponse('workspace', 'index', [ "users" => $usersManager, "usersByEspaceManagerGroup" => $allUsersByEspaceManagerGroup ]);  // templates/index.php
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * TODO: Find a solution to use this method.
	 */
	public function errorAccess(){
		return new TemplateResponse('workspace', 'errorAccess');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function getSubGoupCreate(){

		// $api = curl_init();

		// curl_setopt($api, CURLOPT_URL, "https://nc21.dev.arawa.fr/apps/groupfolders/folders");
		// curl_setopt($api, CURLOPT_HEADER, 1);

		// curl_setopt($api, CURLOPT_HTTPHEADER, [
		// 	'OCS-APIRequest: true',
		// 	'Accept: Application/Json'
		// ]);

		// curl_setopt($api, CURLOPT_COOKIE, $_SESSION['encrypted_session_data']);

		// $res = curl_exec($api);

		// var_dump($api);

		// curl_close($api);

		// var_dump($_COOKIE);

		return new TemplateResponse('workspace', 'subgroupCreation');
	}

}
