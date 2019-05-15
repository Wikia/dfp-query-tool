<?php

namespace Report\Api;

use Common\Api\Authenticator;
use Google\AdsApi\AdManager\AdManagerServices;
use Google\AdsApi\AdManager\Util\v201902\AdManagerDateTimes;
use Google\AdsApi\AdManager\Util\v201902\ReportDownloader;
use Google\AdsApi\AdManager\v201902\ReportJob;
use Google\AdsApi\AdManager\v201902\ReportQuery;
use Google\AdsApi\AdManager\v201902\ReportService as AdManagerReportService;
use Symfony\Component\HttpFoundation\ParameterBag;

class ReportService
{
	const COLUMN_MAPPING = [
		'adServerImpressions' => 'AD_SERVER_IMPRESSIONS',
		'adServerClicks' => 'AD_SERVER_CLICKS',
		'adServerActiveViewViewableImpressionsRate' => 'AD_SERVER_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE',
		'lineItemEndDateTime' => 'LINE_ITEM_END_DATE_TIME',
		'lineItemStartDateTime' => 'LINE_ITEM_START_DATE_TIME',
		'masterCompanionCreativeId' => 'MASTER_COMPANION_CREATIVE_ID',
		'orderTrafficker' => 'ORDER_TRAFFICKER',
		'totalActiveViewEligibleImpressions' => 'TOTAL_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS',
		'totalActiveViewMeasurableImpressions' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS',
		'totalActiveViewViewableImpressions' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS',
		'totalActiveViewMeasurableImpressionsRate' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS_RATE',
		'totalActiveViewViewableImpressionsRate' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE',

		'ad_server_impressions' => 'AD_SERVER_IMPRESSIONS',
		'ad_server_clicks' => 'AD_SERVER_CLICKS',
		'ad_server_active_view_viewable_impressions_rate' => 'AD_SERVER_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE',
		'line_item_end_date_time' => 'LINE_ITEM_END_DATE_TIME',
		'line_item_start_date_time' => 'LINE_ITEM_START_DATE_TIME',
		'master_companion_creative_id' => 'MASTER_COMPANION_CREATIVE_ID',
		'order_trafficker' => 'ORDER_TRAFFICKER',
		'total_active_view_eligible_impressions' => 'TOTAL_ACTIVE_VIEW_ELIGIBLE_IMPRESSIONS',
		'total_active_view_measurable_impressions' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS',
		'total_active_view_viewable_impressions' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS',
		'total_active_view_measurable_impressions_rate' => 'TOTAL_ACTIVE_VIEW_MEASURABLE_IMPRESSIONS_RATE',
		'total_active_view_viewable_impressions_rate' => 'TOTAL_ACTIVE_VIEW_VIEWABLE_IMPRESSIONS_RATE'
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
		'lineItemEndDateTime' => 'LINE_ITEM_END_DATE_TIME',
		'lineItemId' => 'LINE_ITEM_ID',
		'lineItemName' => 'LINE_ITEM_NAME',
		'lineItemStartDateTime' => 'LINE_ITEM_START_DATE_TIME',
		'masterCompanionCreativeId' => 'MASTER_COMPANION_CREATIVE_ID',
		'orderId' => 'ORDER_ID',
		'orderName' => 'ORDER_NAME',
		'orderTrafficker' => 'ORDER_TRAFFICKER',
		'orderTraffickerId' => 'ORDER_TRAFFICKER_ID',
		'targetingValueId' => 'CUSTOM_TARGETING_VALUE_ID',

		'ad_unit_name' => 'AD_UNIT_NAME',
		'creative_id' => 'CREATIVE_ID',
		'creative_name' => 'CREATIVE_NAME',
		'creative_size' => 'CREATIVE_SIZE',
		'custom_criteria' => 'CUSTOM_CRITERIA',
		'device_category' => 'DEVICE_CATEGORY_NAME',
		'key_values' => 'AD_REQUEST_CUSTOM_CRITERIA',
		'line_item_end_date_time' => 'LINE_ITEM_END_DATE_TIME',
		'line_item_id' => 'LINE_ITEM_ID',
		'line_item_name' => 'LINE_ITEM_NAME',
		'line_item_start_date_time' => 'LINE_ITEM_START_DATE_TIME',
		'master_companion_creative_id' => 'MASTER_COMPANION_CREATIVE_ID',
		'order_id' => 'ORDER_ID',
		'order_name' => 'ORDER_NAME',
		'order_trafficker' => 'ORDER_TRAFFICKER',
		'order_trafficker_id' => 'ORDER_TRAFFICKER_ID',
		'targeting_value_id' => 'CUSTOM_TARGETING_VALUE_ID',
	];

	const DIMENSIONS_ATTRIBUTES_MAPPING = [
		'line_item_start_date_time' => 'LINE_ITEM_START_DATE_TIME',
		'line_item_end_date_time' => 'LINE_ITEM_END_DATE_TIME',
		'order_trafficker' => 'ORDER_TRAFFICKER'
	];

	private $adManagerServices;

	public function __construct() {
		$this->adManagerServices = new AdManagerServices();
	}

	public function query(ParameterBag $parameters, \DateTime $startDate) {
		$session = Authenticator::getSession();

		$columns = $this->getColumns($parameters);
		$dimensions = $this->getDimensions($parameters);
		$dimensionsAttributes = $this->getDimensionsAttributes($parameters);

		$endDate = $this->getEndDate($startDate);

		try {
			$reportService = $this->adManagerServices->get($session, AdManagerReportService::class);

			$reportQuery = new ReportQuery();
			$reportQuery->setDimensions($dimensions);
			$reportQuery->setColumns($columns);
			if (!empty($dimensionsAttributes)) {
				$reportQuery->setDimensionAttributes($dimensionsAttributes);
			}
			$statement = StatementBuilder::build($parameters);
			$reportQuery->setStatement($statement);
			if ($parameters->has('custom_field_ids')) {
				$reportQuery->setCustomFieldIds($parameters->get('custom_field_ids'));
			}
			$reportQuery->setDateRangeType('CUSTOM_DATE');
			$reportQuery->setStartDate(AdManagerDateTimes::fromDateTime($startDate)->getDate());
			$reportQuery->setEndDate(AdManagerDateTimes::fromDateTime($endDate)->getDate());

			return $this->run($reportService, $reportQuery);
		} catch (\Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		}
	}

	public function postQuery(ParameterBag $parameters, \DateTime $date) {
		$filters = [];
		$filterValues = $parameters->get('filterValues');
		$filterTypes = $parameters->get('filterTypes');
		foreach ($filterValues as $key => $value) {
			$type = $type = $filterTypes[$key];
			$filters[$type] = $value;
		}
		$parameters->set('filters', $filters);

		return $this->query($parameters, new \DateTime('-1 day', $date));
	}

	public function getReport($id) {
		try {
			$session = Authenticator::getSession();
			$reportService = $this->adManagerServices->get($session, AdManagerReportService::class);

			return $this->downloadReport($reportService, $id);
		} catch (\Exception $e) {
			return sprintf("%s\n", $e->getMessage());
		}
	}

	private function run($reportService, $reportQuery) {
		$reportJob = new ReportJob();
		$reportJob->setReportQuery($reportQuery);
		$reportJob = $reportService->runReportJob($reportJob);

		return $this->downloadReport($reportService, $reportJob->getId());
	}

	private function downloadReport($reportService, $id) {
		$reportDownloader = new ReportDownloader($reportService, $id);
		$reportDownloader->waitForReportToFinish();

		$content = $reportDownloader->downloadReport('CSV_DUMP')->getContents();
		return $this->parseCsvData(gzdecode($content));
	}

	private function parseCsvData($csv) {
		$columns = [];
		$data = [];
		$header = true;

		foreach(preg_split("/((\r?\n)|(\r\n?))/", $csv) as $line) {
			if ($line === "") {
				continue;
			}

			$values = str_getcsv($line);
			if ($header) {
				$columns = $this->parseHeaders($values, $columns);
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
		}, $parameters->get('dimensions_attributes', []));
	}

	/**
	 * @param $startDate
	 * @return \DateTime
	 */
	private function getEndDate(\DateTime $startDate) {
        	/** @var \DateTime $endDate */
        	$endDate = clone $startDate;
        	return $endDate;
	}

	/**
	* @param $values
	* @param $columns
	* @return array
	*/
	private function parseHeaders($values, $columns): array {
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

		return $columns;
	}
}
