<?php

namespace OCA\Workspace\Exceptions\Middleware;

use OCP\AppFramework\Http;

class BadRequestException extends \Exception {
	public function __construct(
		string $message = '',
		int $code = Http::STATUS_BAD_REQUEST,
	) {
		parent::__construct($message, $code);
	}
}
