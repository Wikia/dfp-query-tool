<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\Util\v202002\StatementBuilder;
use Google\AdsApi\AdManager\v202002\Order;

class OrderService {
	private $orderService;

	function __construct() {
		$this->orderService = AdManagerService::get(\Google\AdsApi\AdManager\v202002\OrderService::class);
	}

	public function getById($orderId) {
        $statementBuilder = new StatementBuilder();
        $statementBuilder->Where('id = :id and isArchived = false');
        $statementBuilder->WithBindVariableValue('id', $orderId);

        $page = $this->orderService->getOrdersByStatement($statementBuilder->toStatement());

        $results = $page->getResults();
        if ($results === null) {
            throw new LineItemException('Cannot find order');
        }

        return array_shift($results);
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
