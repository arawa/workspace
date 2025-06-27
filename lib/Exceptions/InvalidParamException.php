<?php

namespace OCA\Workspace\Exceptions;

use OCP\AppFramework\Http;

class InvalidParamException extends \Exception {
	public function __construct(string $message, int $code = Http::STATUS_BAD_REQUEST) {
		parent::__construct($message, $code);
	}
}
