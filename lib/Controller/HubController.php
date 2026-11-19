<?php

namespace OCA\Workspace\Controller;

use OCA\Workspace\Service\Hub\AppIconService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;

class HubController extends Controller {
	public function __construct(
		private AppIconService $appIconService,
	) {
	}

	#[FrontpageRoute(
		verb: 'GET',
		url: '/hub/app/{appName}/icon'
	)]
	#[NoAdminRequired]
	public function getAppIcon(string $appName): JSONResponse {
		$icons = $this->appIconService->getIcons($appName);

		if ($icons === null) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse($icons);
	}
}
