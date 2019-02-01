<?php

namespace Inventory\Api;

use Google\AdsApi\Dfp\Util\v201805\StatementBuilder;
use Google\AdsApi\Dfp\v201805\ArchiveAdUnits;
use Google\AdsApi\Dfp\v201805\InventoryService;

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
			->where('adUnitCode = :adUnitCode AND parentId = :parentId')
			->limit($pageSize)
			->withBindVariableValue('adUnitCode', $adUnitCode)
			// archive top level only
			->withBindVariableValue('parentId', $networkAdUnitId);

		$parentId = null;
		$totalResultSetSize = 0;
		do {
			$page = $inventoryService->getAdUnitsByStatement(
				$statementBuilder->toStatement()
			);
			if ($page->getResults() !== null) {
				$totalResultSetSize = $page->getTotalResultSetSize();
				if (count($page->getResults()) > 1) {
					throw new \InvalidArgumentException('More than 1 ad unit found.');
				}
				foreach ($page->getResults() as $adUnit) {
					printf('Archiving %s...' . PHP_EOL, $adUnit->getAdUnitCode());
					$this->archiveByAncestor($adUnit);
				}
			}
			$statementBuilder->increaseOffsetBy($pageSize);
		} while ($statementBuilder->getOffset() < $totalResultSetSize);
	}

	private function archiveByAncestor($ancestorAdUnit) {
		$inventoryService = DfpService::get(InventoryService::class);

		$pageSize = 100; //StatementBuilder::SUGGESTED_PAGE_LIMIT;
		$statementBuilder = (new StatementBuilder())
			->where('ancestorId = :ancestorId AND status != :status')
			->limit($pageSize)
			->withBindVariableValue('ancestorId', $ancestorAdUnit->getId())
			->withBindVariableValue('status', 'ARCHIVED');

		$times = [];
		$totalResultSetSize = 0;
		do {
			$start = microtime(true);
			$page = $inventoryService->getAdUnitsByStatement(
				$statementBuilder->toStatement()
			);
			$i = $page->getStartIndex();
			if (count($page->getResults()) === 0) {
				$statementBuilder->increaseOffsetBy($pageSize);
				continue;
			}
			if ($page->getResults() !== null) {
				if ($totalResultSetSize === 0) {
					$totalResultSetSize = $page->getTotalResultSetSize();
				}
				$action = new ArchiveAdUnits();

				try {
					$result = $inventoryService->performAdUnitAction(
						$action,
						$statementBuilder->toStatement()
					);
					if ($result === null || $result->getNumChanges() === 0) {
						printf("No ad units were archived.%s", PHP_EOL);
						return;
					}

					$duration = microtime(true) - $start;
					$times[] = $duration;
					$meanTime = array_sum($times)/count($times);

					printf(
						"Archived %d/%d, ETA: %.2fs\n",
						min($i + $pageSize, $totalResultSetSize),
						$totalResultSetSize,
						$meanTime * ($totalResultSetSize - $i) / $pageSize
					);
					$statementBuilder->increaseOffsetBy($pageSize);
				} catch (\Exception $exception) {
					printf("Couldn't archive some ad units.%s", PHP_EOL);
					$statementBuilder->increaseOffsetBy($pageSize);
				}
			}
		} while ($statementBuilder->getOffset() < $totalResultSetSize);
		printf("Finished %s.%s", $ancestorAdUnit->getAdUnitCode(), PHP_EOL);
	}
}
