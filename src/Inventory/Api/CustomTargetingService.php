<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\Util\v201911\StatementBuilder;
use Google\AdsApi\AdManager\v201911\CustomTargetingValue;
use Google\AdsApi\AdManager\v201911\CustomTargetingValueMatchType;

class CustomTargetingService
{
	public function getKeyIds($keys) {
		$ids = [];

		try {
			$customTargetingService = AdManagerService::get(\Google\AdsApi\AdManager\v201911\CustomTargetingService::class);

			$statementBuilder = new StatementBuilder();
			$statementBuilder->where('name = :name');

			foreach ($keys as $key) {
				$statementBuilder->withBindVariableValue('name', $key);

				$page = null;
				for ($i = 0; $i < 10; $i++) {
					$page = $customTargetingService->getCustomTargetingKeysByStatement($statementBuilder->toStatement());

					if ($page) break;
					echo 'SOAP "getCustomTargetingKeysByStatement()" connection error - retrying (' . ($i + 1) . ")...\n";
				}

				$results = $page->getResults();
				if (!empty($results)) {
					foreach ($results as $customTargetingKey) {
						$ids[] = $customTargetingKey->getId();
					}
				} else {
					throw new \Exception(sprintf('Key not found (<error>%s</error>).', $key));
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
			$customTargetingService = AdManagerService::get(\Google\AdsApi\AdManager\v201911\CustomTargetingService::class);

			$statementBuilder = new StatementBuilder();
			$statementBuilder->where('customTargetingKeyId = :customTargetingKeyId AND name = :name');
			$statementBuilder->withBindVariableValue('customTargetingKeyId', $keyId);

			foreach ($values as $value) {
				$statementBuilder->withBindVariableValue('name', trim($value));

				$page = null;
				for ($i = 0; $i < 10; $i++) {
					$page = $customTargetingService->getCustomTargetingValuesByStatement($statementBuilder->toStatement());

					if ($page) break;
					echo 'SOAP "getCustomTargetingKeysByStatement()" connection error - retrying (' . ($i + 1) . ")...\n";
				}

				$results = $page->getResults();
				if (!empty($results)) {
					foreach ($results as $customTargetingValue) {
						$ids[] = $customTargetingValue->getId();
					}
				} else {
					throw new \Exception(sprintf('Value not found (<error>%s</error>).', $value));
				}
			}
		} catch (\Exception $e) {
			throw new CustomTargetingException('Custom targeting error: ' . $e->getMessage());
		}

		return $ids;
	}

	public function addValues($key, $values) {
		$addedValues = 0;
		$keyIds = $this->getKeyIds([$key]);
		$keyId = array_shift($keyIds);
		$packages = array_chunk($values, 200);

		$customTargetingService = AdManagerService::get(\Google\AdsApi\AdManager\v201911\CustomTargetingService::class);
		foreach ($packages as $packageValues) {
			$customTargetingValues = [];

			foreach ($packageValues as $value) {
				$customTargetingValue = new CustomTargetingValue();
				$customTargetingValue->setCustomTargetingKeyId($keyId);
				$customTargetingValue->setDisplayName($value);
				$customTargetingValue->setName($value);
				$customTargetingValue->setMatchType(CustomTargetingValueMatchType::EXACT);

				$customTargetingValues[] = $customTargetingValue;
			}
			$addedValues += count($customTargetingService->createCustomTargetingValues($customTargetingValues));
		}

		return $addedValues;
	}
}
