<?php

namespace OCA\Workspace\Exceptions\Middleware;

use OCP\AppFramework\Http;

class ForbiddenException extends \Exception {
	public function __construct(
		string $message = '',
		int $code = Http::STATUS_FORBIDDEN,
	) {
		parent::__construct($message, $code);
	}
}
