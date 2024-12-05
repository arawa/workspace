<?php

namespace OCA\Workspace\Files\Csv;

use OCA\Workspace\Files\Csv\ImportUsers\Header;
use OCA\Workspace\Files\FileInterface;

class StructureValidator
{
    
    public static function checkCommaAllLines(FileInterface $file): bool {
		$handle = $file->open();
        $nbFieldsRequired = count(Header::FIELDS_REQUIRED);
        while(($data = fgetcsv($handle, 1000, Separator::COMMA)) !== false) {
            if (count($data) < $nbFieldsRequired) {
                return false;
            }
        }

        return true;
    }
}
