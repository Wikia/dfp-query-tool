<?php

namespace Report\Api;

use Symfony\Component\HttpFoundation\ParameterBag;

class ReportService
{
	const COLUMN_MAPPING = [
		'totalActiveViewEligibleImpressions' => 'TOTAL_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS',
		'totalActiveViewMeasurableImpressions' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS',
		'totalActiveViewViewableImpressions' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS',
		'totalActiveViewMeasurableImpressionsRate' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS_RATE',
		'totalActiveViewViewableImpressionsRate' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE'
	];
	const DIMENSION_MAPPING = [
		'adUnitName' => 'AD_UNIT_NAME',
		'country' => 'COUNTRY_NAME',
		'creativeId' => 'CREATIVE_ID',
		'creativeName' => 'CREATIVE_NAME',
		'creativeSize' => 'CREATIVE_SIZE',
		'date' => 'DATE',
		'deviceCategory' => 'DEVICE_CATEGORY_NAME',
		'keyValues' => 'AD_REQUEST_CUSTOM_CRITERIA',
		'lineItemId' => 'LINE_ITEM_ID',
		'lineItemName' => 'LINE_ITEM_NAME',
		'orderId' => 'ORDER_ID',
		'orderName' => 'ORDER_NAME'
	];

	public function postQuery(ParameterBag $request) {
		$user = Authenticator::getUser();

		$columns = array_map(function ($key) {
			return self::COLUMN_MAPPING[$key];
		}, $request->get('metrics'));

		$dimensions = array_map(function ($key) {
			return self::DIMENSION_MAPPING[$key];
		}, $request->get('dimensions'));

		$filterValues = $request->get('filterValues');
		$filterTypes = $request->get('filterTypes');
		$statements = [];
		foreach ($filterValues as $key => $value) {
			if ($value === '') {
				continue;
			}
			$type = $filterTypes[$key];
			$filter = self::DIMENSION_MAPPING[$type];
			$statements[] = $filter . ' in (:' . $type . $key . ')';
		}

		try {
			$reportService = $user->GetService('ReportService', 'v201605');

			$reportQuery = new \ReportQuery();
			$reportQuery->dimensions = $dimensions;
			$reportQuery->columns = $columns;

			$statementBuilder = new \StatementBuilder();
			$statementBuilder->Where(implode(' and ', $statements));

			foreach ($filterValues as $key => $value) {
				if ($value === '') {
					continue;
				}
				$type = $filterTypes[$key];
				if (strpos($type, 'Id') !== false) {
					$value = intval($value);
				}
				$statementBuilder->WithBindVariableValue($type . $key, $value);
			}

			$reportQuery->statement = $statementBuilder->ToStatement();
			$reportQuery->dateRangeType = 'CUSTOM_DATE';
			$reportQuery->startDate = \DateTimeUtils::ToDfpDateTime(
				new \DateTime($request->get('startDate'), new \DateTimeZone('America/New_York')))->date;
			$reportQuery->endDate = \DateTimeUtils::ToDfpDateTime(
				new \DateTime($request->get('endDate'), new \DateTimeZone('America/New_York')))->date;

			$reportJob = new \ReportJob();
			$reportJob->reportQuery = $reportQuery;
			$reportJob = $reportService->runReportJob($reportJob);

			return $this->downloadReport($reportService, $reportJob->id);
		} catch (\Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		}
	}

	public function getReport($id) {
		try {
			$user = Authenticator::getUser();
			$reportService = $user->GetService('ReportService', 'v201605');

			return $this->downloadReport($reportService, $id);
		} catch (\Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		}
	}

	private function downloadReport($reportService, $id) {
		$reportDownloader = new \ReportDownloader($reportService, $id);
		$reportDownloader->waitForReportReady();

		return $this->parseCsvData(gzdecode($reportDownloader->downloadReport('CSV_DUMP')));
	}

	private function parseCsvData($csv) {
		$columns = [];
		$data = [];
		$header = true;

		foreach(preg_split("/((\r?\n)|(\r\n?))/", $csv) as $line) {
			if ($line === "") {
				continue;
			}

			$values = explode(',', $line);
			if ($header) {
				foreach ($values as $value) {
					list($key, $enum) = explode('.', $value);
					$columns[] = $enum;
				}
				$header = false;
			} else {
				$row = [];
				foreach ($values as $key => $value) {
					$row[$columns[$key]] = $value;
				}
				$data[] = $row;
			}
		}

		return $data;
	}
}