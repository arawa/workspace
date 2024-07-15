<?php

namespace OCA\Workspace\Files\Csv;

use OCA\Workspace\Files\BasicStreamInterface;

/**
 * Use CscReader to read without consuming too much memory.
 */
class CsvReader {
	public readonly array $headers;

	public function __construct(private BasicStreamInterface $file) {
		$handle = $file->open();
		$this->headers = fgetcsv($handle, 1000, Separator::COMMA);
		$file->close();
	}

	public function read(): \Generator {
		$handle = $this->file->open();
		fgetcsv($handle, 1000, Separator::COMMA);

		try {
			while(($data = fgetcsv($handle, 1000, Separator::COMMA)) !== false) {
				yield array_combine($this->headers, $data);
			}
		} catch (\Exception $reason) {
			throw new \Exception($reason->getMessage());
		} finally {
			$this->file->close();
		}
	}
}
