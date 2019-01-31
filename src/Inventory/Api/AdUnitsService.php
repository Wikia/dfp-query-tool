<?php

namespace Inventory\Api;

use Google\AdsApi\Dfp\Util\v201805\StatementBuilder;
use Google\AdsApi\Dfp\v201805\ApproveSuggestedAdUnits as ApproveSuggestedAdUnitsAction;
use Google\AdsApi\Dfp\v201805\ArchiveAdUnits;
use Google\AdsApi\Dfp\v201805\InventoryService;
use Google\AdsApi\Dfp\v201805\SuggestedAdUnitService;
use Inventory\Command\ArchiveAdUnitsCommand;

class AdUnitsService
{
	public function archive($adUnitCode) {
		$this->archiveByAdUnitCode($adUnitCode);
	}

	private function archiveByAdUnitCode($adUnitCode) {
		$inventoryService = DfpService::get(InventoryService::class);
		$networkAdUnitId = 81570852;

		$pageSize = StatementBuilder::SUGGESTED_PAGE_LIMIT;
		$statementBuilder = (new StatementBuilder())
			->where('parentId = :parentId')
			->orderBy('id ASC')
			->limit($pageSize)
			->withBindVariableValue('parentId', $networkAdUnitId);

		$parentId = null;
		$totalResultSetSize = 0;
		do {
			$page = $inventoryService->getAdUnitsByStatement(
				$statementBuilder->toStatement()
			);
			if ($page->getResults() !== null) {
				$totalResultSetSize = $page->getTotalResultSetSize();
				foreach ($page->getResults() as $adUnit) {
					if ($adUnit->getAdUnitCode() === $adUnitCode) {
						$this->archiveAdUnitAndChildren($adUnit, $adUnit->getAdUnitCode());
						printf("%s\n", $adUnit->getAdUnitCode());
					}
				}
			}
			$statementBuilder->increaseOffsetBy($pageSize);
		} while ($statementBuilder->getOffset() < $totalResultSetSize);
	}

	private function archiveAdUnitAndChildren($parentAdUnit, $fullAdUnitCode = '') {
		$inventoryService = DfpService::get(InventoryService::class);

		$pageSize = StatementBuilder::SUGGESTED_PAGE_LIMIT;
		$statementBuilder = (new StatementBuilder())
			->where('(parentId = :parentId or id = :parentId) AND status = :status')
			->orderBy('id ASC')
			->limit($pageSize)
			->withBindVariableValue('parentId', $parentAdUnit->getId())
			->withBindVariableValue('status', 'ACTIVE');

		$totalResultSetSize = 0;
		do {
			$page = $inventoryService->getAdUnitsByStatement(
				$statementBuilder->toStatement()
			);
			if ($page->getResults() !== null) {
				$totalResultSetSize = $page->getTotalResultSetSize();
				if (count($page->getResults()) === 1) {
					return;
				}
				foreach ($page->getResults() as $adUnit) {
					if ($adUnit->getId() === $parentAdUnit->getId()) {
						continue;
					}
					$adUnitCode = implode([ $fullAdUnitCode, $adUnit->getAdUnitCode() ], '/');
					$this->archiveAdUnitAndChildren($adUnit, $adUnitCode);
					printf("%s\n", $adUnitCode);
				}
			}
			$statementBuilder->increaseOffsetBy($pageSize);
		} while ($statementBuilder->getOffset() < $totalResultSetSize);

		if ($totalResultSetSize > 0) {
			$statementBuilder->removeLimitAndOffset();
			$action = new ArchiveAdUnits();
			try {
				$result = $inventoryService->performAdUnitAction(
					$action,
					$statementBuilder->toStatement()
				);
				if ($result === null || $result->getNumChanges() === 0) {
					printf("No ad units were archived.%s", PHP_EOL);
				}
			} catch (\Exception $exception) {
				printf("Couldn't archive some ad units.%s", PHP_EOL);
			}
		}
	}
}
