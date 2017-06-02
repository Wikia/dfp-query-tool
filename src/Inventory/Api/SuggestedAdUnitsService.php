<?php

namespace Inventory\Api;

use Google\AdsApi\Dfp\Util\v201705\StatementBuilder;
use Google\AdsApi\Dfp\v201705\ApproveSuggestedAdUnits as ApproveSuggestedAdUnitsAction;
use Google\AdsApi\Dfp\v201705\SuggestedAdUnitService;

class SuggestedAdUnitsService
{
	public function approve() {
		$suggestedAdUnitService = DfpService::get(SuggestedAdUnitService::class);

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

		$action = new ApproveSuggestedAdUnitsAction();
		$result = $suggestedAdUnitService->performSuggestedAdUnitAction($action, $statementBuilder->toStatement());

		$now = new \DateTime();
		$filePath = __DIR__ . '/../../../logs/approved-adunits-' . $now->format('YmdHi') . '.log';

		if ($result !== null && $result->getNumChanges() > 0) {
			printf("Number of suggested ad units approved: %d\n", $result->getNumChanges());
			file_put_contents($filePath, $logContent);
		} else {
			printf("No suggested ad units were approved.\n");
		}
	}
}
