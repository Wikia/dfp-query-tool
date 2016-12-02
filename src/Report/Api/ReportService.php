<?php

namespace Report\Api;

use Common\Api\Authenticator;
use Symfony\Component\HttpFoundation\ParameterBag;

class ReportService
{
	const COLUMN_MAPPING = [
		'adServerImpressions' => 'AD_SERVER_IMPRESSIONS',
		'adServerClicks' => 'AD_SERVER_CLICKS',
		'adServerActiveViewViewableImpressionsRate' => 'AD_SERVER_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE',
		'totalActiveViewEligibleImpressions' => 'TOTAL_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS',
		'totalActiveViewMeasurableImpressions' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS',
		'totalActiveViewViewableImpressions' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS',
		'totalActiveViewMeasurableImpressionsRate' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS_RATE',
		'totalActiveViewViewableImpressionsRate' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE',

		'ad_server_impressions' => 'AD_SERVER_IMPRESSIONS',
		'ad_server_clicks' => 'AD_SERVER_CLICKS',
		'ad_server_active_view_viewable_impressions_rate' => 'AD_SERVER_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE',
		'total_active_view_eligible_impressions' => 'TOTAL_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS',
		'total_active_view_measurable_impressions' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS',
		'total_active_view_viewable_impressions' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS',
		'total_active_view_measurable_impressions_rate' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS_RATE',
		'total_active_view_viewable_impressions_rate' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE',
		'line_item_start_date_time' => 'LINE_ITEM_START_DATE_TIME',
		'line_item_end_date_time' => 'LINE_ITEM_END_DATE_TIME',
		'order_trafficer' => 'ORDER_TRAFFICER',
		'master_companion_creative_id' => 'MASTER_COMPANION_CREATIVE_ID'
	];
	const DIMENSION_MAPPING = [
		'adUnitName' => 'AD_UNIT_NAME',
		'country' => 'COUNTRY_NAME',
		'creativeId' => 'CREATIVE_ID',
		'creativeName' => 'CREATIVE_NAME',
		'creativeSize' => 'CREATIVE_SIZE',
		'customCriteria' => 'CUSTOM_CRITERIA',
		'date' => 'DATE',
		'deviceCategory' => 'DEVICE_CATEGORY_NAME',
		'keyValues' => 'AD_REQUEST_CUSTOM_CRITERIA',
		'lineItemId' => 'LINE_ITEM_ID',
		'lineItemName' => 'LINE_ITEM_NAME',
		'masterCompanionCreativeId' => 'MASTER_COMPANION_CREATIVE_ID',
		'orderId' => 'ORDER_ID',
		'orderName' => 'ORDER_NAME',
		'orderTraffickerId' => 'ORDER_TRAFFICKER_ID',
		'targetingValueId' => 'CUSTOM_TARGETING_VALUE_ID',

		'ad_unit_name' => 'AD_UNIT_NAME',
		'creative_id' => 'CREATIVE_ID',
		'creative_name' => 'CREATIVE_NAME',
		'creative_size' => 'CREATIVE_SIZE',
		'custom_criteria' => 'CUSTOM_CRITERIA',
		'device_category' => 'DEVICE_CATEGORY_NAME',
		'key_values' => 'AD_REQUEST_CUSTOM_CRITERIA',
		'line_item_id' => 'LINE_ITEM_ID',
		'line_item_name' => 'LINE_ITEM_NAME',
		'master_companion_creative_id' => 'MASTER_COMPANION_CREATIVE_ID',
		'order_id' => 'ORDER_ID',
		'order_name' => 'ORDER_NAME',
		'order_trafficker_id' => 'ORDER_TRAFFICKER_ID',
		'targeting_value_id' => 'CUSTOM_TARGETING_VALUE_ID',
		'line_item_start_date_time' => 'LINE_ITEM_START_DATE_TIME',
		'line_item_end_date_time' => 'LINE_ITEM_END_DATE_TIME',
		'order_trafficker' => 'ORDER_TRAFFICKER'
	];

	const DIMENSIONS_ATTRIBUTES_MAPPING = [
		'line_item_start_date_time' => 'LINE_ITEM_START_DATE_TIME',
		'line_item_end_date_time' => 'LINE_ITEM_END_DATE_TIME',
		'order_trafficker' => 'ORDER_TRAFFICKER'
	];

	public function query(ParameterBag $parameters) {
		$user = Authenticator::getUser();

		$columns = $this->getColumns($parameters);
		$dimensions = $this->getDimensions($parameters);
		$dimensionsAttributes = $this->getDimensionsAttributes($parameters);
		$startDate = new \DateTime('-1 day', new \DateTimeZone('Europe/Warsaw'));
		$endDate = new \DateTime('now', new \DateTimeZone('Europe/Warsaw'));
		$startDate->setTime(0, 0, 0);
		$endDate->setTime(0, 0, 0);

		try {
			$reportService = $user->GetService('ReportService', 'v201605');

			$reportQuery = new \ReportQuery();
			$reportQuery->dimensions = $dimensions;
			$reportQuery->columns = $columns;
			$reportQuery->dimensionAttributes = $dimensionsAttributes;
			$reportQuery->statement = StatementBuilder::build($parameters);
			if ($parameters->has('custom_field_ids')) {
				$reportQuery->customFieldIds = $parameters->get('custom_field_ids');
			}
			$reportQuery->dateRangeType = 'CUSTOM_DATE';
			$reportQuery->startDate = \DateTimeUtils::ToDfpDateTime($startDate)->date;
			$reportQuery->endDate = \DateTimeUtils::ToDfpDateTime($endDate)->date;

			return $this->run($reportService, $reportQuery);
		} catch (\Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		}
	}

	public function postQuery(ParameterBag $parameters) {
		$filters = [];
		$filterValues = $parameters->get('filterValues');
		$filterTypes = $parameters->get('filterTypes');
		foreach ($filterValues as $key => $value) {
			$type = $type = $filterTypes[$key];
			$filters[$type] = $value;
		}
		$parameters->set('filters', $filters);

		return $this->query($parameters);
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

	private function run($reportService, $reportQuery) {
		$reportJob = new \ReportJob();
		$reportJob->reportQuery = $reportQuery;
		$reportJob = $reportService->runReportJob($reportJob);

		return $this->downloadReport($reportService, $reportJob->id);
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
					$enum = $value;
					if (strpos($value, '.') !== false) {
						list($key, $enum) = explode('.', $value);
					} else if (strpos($value, 'CF[') !== false) {
						$enum = strtr($value, [
							'[' => '_',
							']_Value' => ''
						]);
					}
					$columns[] = $enum;
				}
				$header = false;
			} else {
				$row = [];
				$skip = false;
				foreach ($values as $key => $value) {
					$row[$columns[$key]] = $value;
					if ($value === '-') {
						$skip = true;
					}
				}
				if (!$skip) {
					$data[] = $row;
				}
			}
		}
		return $data;
	}

	private function getColumns(ParameterBag $parameters) {
		return array_map(function ($key) {
			return self::COLUMN_MAPPING[$key];
		}, $parameters->get('metrics'));
	}

	private function getDimensions(ParameterBag $parameters) {
		return array_map(function ($key) {
			return self::DIMENSION_MAPPING[$key];
		}, $parameters->get('dimensions'));
	}

	private function getDimensionsAttributes(ParameterBag $parameters) {
		return array_map(function ($key) {
			return self::DIMENSIONS_ATTRIBUTES_MAPPING[$key];
		}, $parameters->get('dimensions_attributes'));
	}
}
