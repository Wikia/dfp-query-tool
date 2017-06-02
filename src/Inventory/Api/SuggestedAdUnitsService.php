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

		$action = new ApproveSuggestedAdUnitsAction();
		$result = $suggestedAdUnitService->performSuggestedAdUnitAction($action,
			$statementBuilder->toStatement());
		if ($result !== null && $result->getNumChanges() > 0) {
			printf("Number of suggested ad units approved: %d\n", $result->getNumChanges());
		} else {
			printf("No suggested ad units were approved.\n");
		}
	}
}
