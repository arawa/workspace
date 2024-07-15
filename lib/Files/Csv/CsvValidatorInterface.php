<?php

namespace OCA\Workspace\Files\Csv;

use OCA\Workspace\Files\BasicStreamInterface;

interface CsvValidatorInterface {
	public function validate(BasicStreamInterface $file): bool;
}
