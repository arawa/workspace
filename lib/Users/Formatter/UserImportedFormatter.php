<?php

namespace OCA\Workspace\Users\Formatter;

class UserImportedFormatter {
	public function __construct(
		public string $uid,
		public string $role
	) {
	}
}
