<?php

namespace Inventory\Api;

use Common\Api\Authenticator;
use Symfony\Component\Yaml\Exception\RuntimeException;

class LineItemService
{
	public function create($form) {
		try {
			$user = Authenticator::getUser();

			$lineItemService = $user->GetService('LineItemService', 'v201608');

			$inventoryTargeting = new \InventoryTargeting();
			$inventoryTargeting->targetedAdUnits = [ $this->getRootAdUnit($user) ];

			$targeting = new \Targeting();
			$targeting->inventoryTargeting = $inventoryTargeting;

			$orderId = '552241452';
			$lineItem = new \LineItem();
			$lineItem->name = $form['sizes'];
			$lineItem->orderId = $orderId;
			$lineItem->targeting = $targeting;
			$lineItem->lineItemType = 'STANDARD';
			$lineItem->allowOverbook = true;

			$lineItem->creativePlaceholders = $this->getCreativePlaceholders($form['sizes']);
			// Set the creative rotation type to even.
			$lineItem->creativeRotationType = 'EVEN';
			// Set the length of the line item to run.
			$lineItem->startDateTimeType = 'IMMEDIATELY';
			$lineItem->endDateTime = \DateTimeUtils::ToDfpDateTime(
				new \DateTime('+1 month', new \DateTimeZone('America/New_York')));
			// Set the cost per unit to $2.
			$lineItem->costType = 'CPM';
			$lineItem->costPerUnit = new \Money('USD', 2000000);
			// Set the number of units bought to 500,000 so that the budget is
			// $1,000.
			$goal = new \Goal();
			$goal->units = 500000;
			$goal->unitType = 'IMPRESSIONS';
			$goal->goalType = 'LIFETIME';
			$lineItem->primaryGoal = $goal;
			// Create the line items on the server.
			$lineItems = $lineItemService->createLineItems([ $lineItem ]);
			// Display results.
			if (isset($lineItems)) {
				foreach ($lineItems as $lineItem) {
					printf("A line item with with ID %d, belonging to order ID %d, and name "
						. "%s was created\n", $lineItem->id, $lineItem->orderId,
						$lineItem->name);
				}
			}
		} catch (\Exception $e) {
			throw new RuntimeException($e->getMessage());
		}
	}

	private function getRootAdUnit($user) {
		$networkService = $user->GetService('NetworkService', 'v201608');

		$network = $networkService->getCurrentNetwork();

		$adUnit = new \AdUnitTargeting();
		$adUnit->adUnitId = $network->effectiveRootAdUnitId;
		$adUnit->includeDescendants = true;

		return $adUnit;
	}

	private function getCreativePlaceholders($sizeList) {
		$placeholders = [];
		$sizes = explode(',', $sizeList);

		foreach ($sizes as $size) {
			list($width, $height) = explode('x', $size);
			$creativePlaceholder = new \CreativePlaceholder();
			$creativePlaceholder->size = new \Size($width, $height, false);
			$placeholders[] = $creativePlaceholder;
		}

		return $placeholders;
	}
}
