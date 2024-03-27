<?php

namespace OCA\Workspace\Response;

use OCA\Workspace\Notifications\ToastMessager;

/**
 * A formatter for a Response class type if an error is encounter.
 * @uses ErrorResponseFormatter::format($exception)
 */
class ErrorResponseFormatter {
	public static function format(ToastMessager $notification, \Exception $exception): array {

		$data = array_merge(
			self::createMedata($exception),
			self::createData($notification),
			self::createDataException($exception)
		);

		return $data;
	}

	private static function createMedata(\Exception $exception): array {
		$code = $exception->getCode();

		$dataMetadata = [
			'metadata' => [
				'status' => $code >= 200 && $code < 300 ? 'ok' : 'failure',
				'status_code' => $code,
				'message' => $exception->getMessage()
			]
		];

		return $dataMetadata;
	}

	private static function createData(ToastMessager $notification): array {
		$dataEndUser = [];

		$dataEndUser['data'] = [
			'title' => $notification->getTitle(),
			'message' => $notification->getMessage(),
		];

		return $dataEndUser;
	}

	private static function createDataException(\Exception $exception): array {
		$dataException = [];

		$dataException['exception'] = [
			/**
			 * string
			 */
			'exception_message' => $exception->getMessage(),

			/**
			 * int
			 */
			'exception_code' => $exception->getCode(),

			/**
			 * array
			 */
			'exception_file' => [

				/**
				 * string - absolute path.
				 */
				'name' => $exception->getFile(),

				/**
				 * int - the line number of the file.
				 */
				'line' => $exception->getLine(),
			],

			/**
			 * array
			 */
			'exception_trace' => $exception->getTrace(),

			/**
			 * string
			 */
			'exception_trace_string' => $exception->getTraceAsString()
		];

		return $dataException;
	}
}
