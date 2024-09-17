<?php

namespace OCA\Workspace\Files\Csv;

use OCA\Workspace\Files\BasicStreamInterface;

interface CsvParserInterface {
	public function parser(BasicStreamInterface $file): array;
}
