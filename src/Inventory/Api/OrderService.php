<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\v201902\Order;

class OrderService {
	private $orderService;

	function __construct() {
		$this->orderService = AdManagerService::get(\Google\AdsApi\AdManager\v201902\OrderService::class);
	}

	public function create($form) {
		$order = new Order();
		$order->setName($form['orderName']);
		$order->setAdvertiserId($form['advertiserId']);
		$order->setTraffickerId($form['traffickerId']);

		$result = $this->orderService->createOrders([$order]);

		return $result[0]->getId();
	}
}
