<?php

namespace Inventory\Api;

use Common\Api\Authenticator;

class LineItemService
{
	private $customTargetingService;

	public function __construct() {
		$this->customTargetingService = new CustomTargetingService();
	}

	public function create($form) {
		$this->validateForm($form);

		try {
			$user = Authenticator::getUser();

			$lineItemService = $user->GetService('LineItemService', 'v201608');

			$inventoryTargeting = new \InventoryTargeting();
			$inventoryTargeting->targetedAdUnits = [ $this->getRootAdUnit($user) ];

			$targeting = new \Targeting();
			$targeting->inventoryTargeting = $inventoryTargeting;
			$targeting->customTargeting = $this->getCustomTargeting($form);

			$orderId = $form['orderId'];
			$lineItem = new \LineItem();
			$lineItem->name = $form['lineItemName'];
			$lineItem->orderId = $orderId;
			$lineItem->targeting = $targeting;
			$lineItem->allowOverbook = true;

			$lineItem->disableSameAdvertiserCompetitiveExclusion = false;
			if (isset($form['sameAdvertiser'])) {
				$lineItem->disableSameAdvertiserCompetitiveExclusion = true;
			}

			$this->setupType($lineItem, $form);
			$this->setupTimeRange($lineItem, $form);

			$lineItem->costType = 'CPM';
			$lineItem->costPerUnit = new \Money('USD', floatval($form['rate']) * 1000000);

			$lineItem->creativePlaceholders = $this->getCreativePlaceholders($form['sizes']);
			$lineItem->creativeRotationType = 'OPTIMIZED';

			$lineItems = $lineItemService->createLineItems([ $lineItem ]);

			if (isset($lineItems)) {
				foreach ($lineItems as $lineItem) {
					return [
						'id' => $lineItem->id,
						'name' => $lineItem->name,
						'orderId' => $lineItem->orderId
					];
				}
			}
		} catch (CustomTargetingException $e) {
			throw new LineItemException($e->getMessage());
		} catch (\Exception $e) {
			throw new LineItemException('Line item error: ' . $e->getMessage());
		}
	}

	private function validateForm($form) {
		$requiredFields = [
			'orderId',
			'lineItemName',
			'sizes',
			'type',
			'priority',
			'rate'
		];

		foreach ($requiredFields as $field) {
			if (!isset($form[$field]) || $form[$field] === '') {
				throw new LineItemException(sprintf('Invalid form data (<strong>%s</strong>).', $field));
			}
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

	private function getCustomTargeting($form) {
		if (!isset($form['keys']) || count($form['keys']) < 1) {
			return null;
		}

		$set = new \CustomCriteriaSet();
		$set->logicalOperator = 'AND';
		$targetingCriteria = [];

		$keyIds = $this->customTargetingService->getKeyIds($form['keys']);

		$countValues = count($form['values']);
		for ($i = 0; $i < $countValues; $i++) {
			$keyId = $keyIds[$i];
			$values = explode(',', $form['values'][$i]);
			$valueIds = $this->customTargetingService->getValueIds($keyId, $values);

			$criteria = new \CustomCriteria();
			$criteria->keyId = $keyId;
			$criteria->valueIds = $valueIds;
			$criteria->operator = $form['operators'][$i];
			$targetingCriteria[] = $criteria;
		}
		$set->children = $targetingCriteria;

		return $set;
	}

	private function getCreativePlaceholders($sizeList) {
		$placeholders = [];
		$sizes = explode(',', $sizeList);

		foreach ($sizes as $size) {
			list($width, $height) = explode('x', trim($size));
			$creativePlaceholder = new \CreativePlaceholder();
			$creativePlaceholder->size = new \Size(intval($width), intval($height), false);
			$placeholders[] = $creativePlaceholder;
		}

		return $placeholders;
	}

	private function setupType($lineItem, $form) {
		$lineItem->lineItemType = $form['type'];
		$lineItem->priority = $form['priority'];
		switch ($form['type']) {
			case 'STANDARD':
				$goal = new \Goal();
				$goal->units = 500000;
				$goal->unitType = 'IMPRESSIONS';
				$goal->goalType = 'LIFETIME';
				$lineItem->primaryGoal = $goal;
				return;
			case 'PRICE_PRIORITY':
				$goal = new \Goal();
				$goal->goalType = 'NONE';
				$lineItem->primaryGoal = $goal;
				return;
			case 'SPONSORSHIP':
			case 'NETWORK':
			case 'HOUSE':
				$goal = new \Goal();
				$goal->units = 100;
				$lineItem->primaryGoal = $goal;
				return;
		}
	}

	private function setupTimeRange($lineItem, $form) {
		if ($form['start'] !== '') {
			$lineItem->startDateTime = \DateTimeUtils::ToDfpDateTime(new \DateTime($form['start'], new \DateTimeZone('UTC')));
		} else {
			$lineItem->startDateTimeType = 'IMMEDIATELY';
		}
		if ($form['end'] !== '') {
			$lineItem->endDateTime = \DateTimeUtils::ToDfpDateTime(new \DateTime($form['end'], new \DateTimeZone('UTC')));
		} else {
			$lineItem->unlimitedEndDateTime = true;
		}
	}
}
