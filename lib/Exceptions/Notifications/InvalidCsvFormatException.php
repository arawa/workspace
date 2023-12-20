<?php

namespace OCA\Workspace\Exceptions\Notifications;

use OCP\AppFramework\Http;

class InvalidCsvFormatException extends AbstractNotificationException {
	public function __construct(private string $title, string $message, int $code = Http::STATUS_BAD_REQUEST) {
		parent::__construct($title, $message, $code);
	}
}
