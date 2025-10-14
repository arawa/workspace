<?php

namespace OCA\Workspace\Exceptions;

use OCP\AppFramework\Http;

class WorkspaceNameSpecialCharException extends \Exception {
	public function __construct($message = "", $code = Http::STATUS_BAD_REQUEST) {
		parent::__construct(message: $message, code: $code);
	}
}
