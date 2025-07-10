<?php

namespace OCA\Workspace\Service\Validator;

use OCA\Workspace\Exceptions\InvalidParamException;
use OCA\Workspace\Service\Params\WorkspaceEditParams;

class WorkspaceEditParamsValidator {
	private array $expectedTypes = [
		'name' => 'string',
		'color' => 'string',
		'quota' => 'integer'
	];

	public function __construct(
		private ColorValidator $colorValidator,
	) {
	}

	public function validate(array $params): void {
		$params = array_filter($params, fn ($param) => !is_null($param));
		$this->checkAllowedKeys($params);
		$this->checkTypes($params);
		$this->checkColor($params);
		$this->checkQuota($params);
	}

	private function checkAllowedKeys(array $params): void {
		$diff = array_diff(array_keys($params), array_keys(WorkspaceEditParams::DEFAULT));
		if (!empty($diff)) {
			throw new InvalidParamException('These keys are not allowed: ' . implode(', ', $diff));
		}
	}

	private function checkTypes(array $params): void {
		foreach ($this->expectedTypes as $key => $type) {
			if (isset($params[$key]) && gettype($params[$key]) !== $type) {
				throw new InvalidParamException("The {$key} key must be a {$type}");
			}
		}
	}

	private function checkColor(array $params): void {
		if (isset($params['color'])) {
			$this->colorValidator->validate($params['color']);
		}
	}

	private function checkQuota(array $params): void {
		if (isset($params['quota']) && !($params['quota'] === -3 || $params['quota'] > 0)) {
			throw new InvalidParamException('The quota must be -3 or superior to 0');
		}
	}
}
