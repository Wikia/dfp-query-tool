<?php

namespace Tableau\Controller;

use Common\Controller\Controller;

class TableauController extends Controller
{
	public function renderWebConnector() {
		$dimensions = [
			'adUnitName' => 'Ad Unit name',
			'country' => 'Country',
			'creativeId' => 'Creative ID',
			'creativeName' => 'Creative name',
			'creativeSize' => 'Creative size',
			'date' => 'Date',
			'deviceCategory' => 'Device category',
			'keyValues' => 'Key-values',
			'lineItemId' => 'Line item ID',
			'lineItemName' => 'Line item name',
			'orderId' => 'Order ID',
			'orderName' => 'Order name'
		];

		$metrics = [
			'totalActiveViewEligibleImpressions' => 'Total Active View eligible impressions',
			'totalActiveViewMeasurableImpressions' => 'Total Active View measurable impressions',
			'totalActiveViewViewableImpressions' => 'Total Active View viewable impressions',
			'totalActiveViewMeasurableImpressionsRate' => 'Total Active View % measurable impressions',
			'totalActiveViewViewableImpressionsRate' => 'Total Active View % viewable impressions',
		];

		return $this->render('tableau-web-connector', [
			'dimensions' => $dimensions,
			'metrics' => $metrics
		]);
	}
}