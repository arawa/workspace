<?php

namespace OCA\Workspace\Files\Csv;

use OCA\Workspace\Files\ManagerConnectionFileInterface;

interface CsvValidatorInterface
{
    public function validate(ManagerConnectionFileInterface $file): bool;
}
