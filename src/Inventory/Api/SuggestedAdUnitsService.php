<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\Util\v202205\StatementBuilder;
use Google\AdsApi\AdManager\v202205\ApproveSuggestedAdUnits as ApproveSuggestedAdUnitsAction;
use Google\AdsApi\AdManager\v202205\SuggestedAdUnitService;

class SuggestedAdUnitsService
{
	public function approve() {
		$suggestedAdUnitService = AdManagerService::get(SuggestedAdUnitService::class);

		$pageSize = StatementBuilder::SUGGESTED_PAGE_LIMIT;
		$statementBuilder = (new StatementBuilder())->limit($pageSize);

		$totalResultSetSize = 0;
		$logContent = '';
		do {
			$page = $suggestedAdUnitService->getSuggestedAdUnitsByStatement(
				$statementBuilder->toStatement());

			if ($page->getResults() !== null) {
				$totalResultSetSize = $page->getTotalResultSetSize();
				$i = $page->getStartIndex();
				foreach ($page->getResults() as $suggestedAdUnit) {
					$adUnit = [];
					foreach ($suggestedAdUnit->getParentPath() as $parentPath) {
						$adUnit[] = $parentPath->getName();
					}
					$adUnit[] = implode('/', $suggestedAdUnit->getPath());

					$logContent .= sprintf("(%d) %s\n", $suggestedAdUnit->getNumRequests(), implode('/', $adUnit));
					$i++;
				}
			}
			$statementBuilder->increaseOffsetBy($pageSize);
		} while ($statementBuilder->getOffset() < $totalResultSetSize);

		$now = new \DateTime();
		$filePath = __DIR__ . '/../../../log/approved-adunits-' . $now->format('YmdHi') . '.log';

		file_put_contents($filePath, $logContent);
		if ($totalResultSetSize > 0) {
			$statementBuilder->removeLimitAndOffset();
			$action = new ApproveSuggestedAdUnitsAction();
			$result = $suggestedAdUnitService->performSuggestedAdUnitAction($action, $statementBuilder->toStatement());

			if ($result !== null && $result->getNumChanges() > 0) {
				printf("Number of suggested ad units approved: %d\n", $result->getNumChanges());
			} else {
				printf("No suggested ad units were approved.\n");
			}
		} else {
			printf("No suggested ad units to be approved found.\n");
		}
	}
}
