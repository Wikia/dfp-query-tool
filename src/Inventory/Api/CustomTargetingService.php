<?php

namespace Inventory\Api;

use Common\Api\Authenticator;

class CustomTargetingService
{
	public function getKeyIds($keys) {
		$ids = [];

		try {
			$user = Authenticator::getUser();

			$customTargetingService = $user->GetService('CustomTargetingService', 'v201608');

			$statementBuilder = new \StatementBuilder();
			$statementBuilder->Where('name = :name');

			foreach ($keys as $key) {
				$statementBuilder->WithBindVariableValue('name', $key);

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
			$user = Authenticator::getUser();

			$customTargetingService = $user->GetService('CustomTargetingService', 'v201608');

			$statementBuilder = new \StatementBuilder();
			$statementBuilder->Where('customTargetingKeyId = :customTargetingKeyId AND name = :name');
			$statementBuilder->WithBindVariableValue('customTargetingKeyId', $keyId);

			foreach ($values as $value) {
				$statementBuilder->WithBindVariableValue('name', trim($value));

				$page = $customTargetingService->getCustomTargetingValuesByStatement($statementBuilder->ToStatement());

				if (isset($page->results)) {
					foreach ($page->results as $customTargetingValue) {
						$ids[] = $customTargetingValue->id;
					}
				}
			}
		} catch (\Exception $e) {
			throw new CustomTargetingException('Custom targeting error: ' . $e->getMessage());
		}

		if (count($ids) !== count($values)) {
			throw new CustomTargetingException(sprintf('Custom targeting error: At least one value does not exist (<strong>%s</strong>).', implode(',', $values)));
		}

		return $ids;
	}
}
