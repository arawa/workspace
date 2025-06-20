<?php

namespace OCA\Workspace\Exceptions;

use OCP\AppFramework\Http;

class NotFoundException extends \Exception {
	public function __construct(string $message, int $code = Http::STATUS_NOT_FOUND)	{
		parent::__construct($message, $code);
	}
}
