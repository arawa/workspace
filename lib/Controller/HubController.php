<?php

namespace OCA\Workspace\Controller;

use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IURLGenerator;

class HubController extends Controller {
	public function __construct(
		private IAppManager $appManager,
		private IURLGenerator $urlGenerator
	) {
	}
	
	#[FrontpageRoute(
		verb: 'GET',
		url: '/hub/app/{appName}/icon'
	)]
	#[NoAdminRequired]
	public function getAppIcon(string $appName): JSONResponse {
		$iconPath = $this->appManager->getAppIcon($appName);
		$darkIconPath = $this->appManager->getAppIcon($appName, true);
		
		$iconFullPath = file_get_contents($this->urlGenerator->getAbsoluteURL($iconPath));
		$darkIconFullPath = file_get_contents($this->urlGenerator->getAbsoluteURL($darkIconPath));

		return new JSONResponse([
			'icon_path' => $iconFullPath,
			'dark_icon_path' => $darkIconFullPath
		]);
	}
}
