<?php

namespace OCA\Workspace\Files\Csv;

use Exception;
use OCA\Workspace\Files\ManagerConnectionFileInterface;

/**
 * To use this class, you have to call this class as a function in a loop.
 * @example "foreach((new CsvReader)($file) as $data)" use from a loop.
 */
class CsvReader
{
    public function __invoke(ManagerConnectionFileInterface $file): \Generator {
        $row = 0;
        $header = [];
        $handle = $file->open();

        try {
            while(($data = fgetcsv($handle, 1000, ',')) !== false) {
                if ($row === 0) {
                    $header = $data;
                    $row++;
                    continue;
                }

                yield array_combine($header, $data);

                $row++;
            }
        } catch (\Exception $reason){
            throw new Exception($reason->getMessage());
        } finally {
            $file->close();
        }
    }
}
