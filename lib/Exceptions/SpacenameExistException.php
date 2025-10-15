<?php

namespace OCA\Workspace\Exceptions;

use OCP\AppFramework\Http;

class SpacenameExistException extends \Exception {
	public function __construct(
		string $message = "",
		int $code = Http::STATUS_CONFLICT,
	)
	{
		parent::__construct($message, $code);
	}
}
