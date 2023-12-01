<?php

namespace OCA\Workspace\Files\Csv;

use OCA\Workspace\Files\ManagerConnectionFileInterface;

interface CsvParserInterface
{
    public function parser(ManagerConnectionFileInterface $file): array;
}
