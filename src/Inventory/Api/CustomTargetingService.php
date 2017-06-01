<?php

namespace Inventory\Api;

use Google\AdsApi\Dfp\Util\v201705\StatementBuilder;

class CustomTargetingService
{
	public function getKeyIds($keys) {
		$ids = [];

		try {
			$customTargetingService = DfpService::get(\Google\AdsApi\Dfp\v201705\CustomTargetingService::class);

			$statementBuilder = new StatementBuilder();
			$statementBuilder->where('name = :name');

			foreach ($keys as $key) {
				$statementBuilder->withBindVariableValue('name', $key);

				$page = $customTargetingService->getCustomTargetingKeysByStatement($statementBuilder->ToStatement());

				if (isset($page->results)) {
					foreach ($page->results as $customTargetingKey) {
						$ids[] = $customTargetingKey->id;
					}
				} else {
					throw new \Exception(sprintf('Key not found (<strong>%s</strong>).', $key));
				}
			}

		} catch (\Exception $e) {
			throw new CustomTargetingException('Custom targeting error: ' . $e->getMessage());
		}

		return $ids;
	}

	public function getValueIds($keyId, $values) {
		$ids = [];

		try {
			$customTargetingService = DfpService::get(\Google\AdsApi\Dfp\v201705\CustomTargetingService::class);

			$statementBuilder = new StatementBuilder();
			$statementBuilder->where('customTargetingKeyId = :customTargetingKeyId AND name = :name');
			$statementBuilder->withBindVariableValue('customTargetingKeyId', $keyId);

			foreach ($values as $value) {
				$statementBuilder->withBindVariableValue('name', trim($value));

				$page = $customTargetingService->getCustomTargetingValuesByStatement($statementBuilder->ToStatement());

				if (isset($page->results)) {
					foreach ($page->results as $customTargetingValue) {
						$ids[] = $customTargetingValue->id;
					}
				} else {
					throw new \Exception(sprintf('Value not found (<strong>%s</strong>).', $value));
				}
			}
		} catch (\Exception $e) {
			throw new CustomTargetingException('Custom targeting error: ' . $e->getMessage());
		}

		return $ids;
	}
}
