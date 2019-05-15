<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\Util\v201902\AdManagerDateTimes;
use Google\AdsApi\AdManager\Util\v201902\StatementBuilder;
use Google\AdsApi\AdManager\v201902\AdUnitTargeting;
use Google\AdsApi\AdManager\v201902\CreativePlaceholder;
use Google\AdsApi\AdManager\v201902\CustomCriteria;
use Google\AdsApi\AdManager\v201902\CustomCriteriaSet;
use Google\AdsApi\AdManager\v201902\EnvironmentType;
use Google\AdsApi\AdManager\v201902\Goal;
use Google\AdsApi\AdManager\v201902\InventoryTargeting;
use Google\AdsApi\AdManager\v201902\LineItem;
use Google\AdsApi\AdManager\v201902\Money;
use Google\AdsApi\AdManager\v201902\NetworkService;
use Google\AdsApi\AdManager\v201902\Size;
use Google\AdsApi\AdManager\v201902\Targeting;
use Inventory\Form\LineItemForm;

class LineItemService
{
	private $customTargetingService;
	private $lineItemService;
	private $targetedAdUnits;
	private $lineItemCreativeAssociationService;

	public function __construct() {
		$this->customTargetingService = new CustomTargetingService();
		$this->lineItemService = AdManagerService::get(\Google\AdsApi\AdManager\v201902\LineItemService::class);
		$this->targetedAdUnits = [$this->getRootAdUnit()];
		$this->lineItemCreativeAssociationService = new LineItemCreativeAssociationService();
	}

	public function create($form) {
		$this->validateForm($form);

		try {
			$inventoryTargeting = new InventoryTargeting();
			$inventoryTargeting->setTargetedAdUnits($this->targetedAdUnits);

			$targeting = new Targeting();
			$targeting->setInventoryTargeting($inventoryTargeting);
			$targeting->setCustomTargeting($this->getCustomTargeting($form));

			$orderId = $form['orderId'];
			$lineItem = new LineItem();
			$lineItem->setName($form['lineItemName']);
			$lineItem->setOrderId($orderId);
			$lineItem->setTargeting($targeting);
			$lineItem->setAllowOverbook(true);

			$lineItem->setDisableSameAdvertiserCompetitiveExclusion(false);
			if (isset($form['sameAdvertiser'])) {
				$lineItem->setDisableSameAdvertiserCompetitiveExclusion(true);
			}

			$this->setupType($lineItem, $form);
			$this->setupTimeRange($lineItem, $form);

			$lineItem->setCostType('CPM');

			$rate = isset($form['cents']) ? $form['rate'] / 100 : $form['rate'];

			$lineItem->setCostPerUnit(new Money('USD', floatval($rate) * 1000000));

			$lineItem->setCreativePlaceholders($this->getCreativePlaceholders($form['sizes']));
			$lineItem->setCreativeRotationType('OPTIMIZED');

			if ($form['isVideo']) {
				$lineItem->setEnvironmentType(EnvironmentType::VIDEO_PLAYER);
			}

			$lineItems = [];
			for ($i = 0; $i < 10; $i++) {
				$lineItems = $this->lineItemService->createLineItems([ $lineItem ]);

				if ($lineItems && count($lineItems)) break;
				echo 'SOAP "createLineItemCreativeAssociations()" connection error - retrying (' . ($i + 1) . ")...\n";
			}

			if (isset($lineItems)) {
				foreach ($lineItems as $lineItem) {
					return [
						'id' => $lineItem->getId(),
						'name' => $lineItem->getName(),
						'orderId' => $lineItem->getOrderId()
					];
				}
			}
		} catch (CustomTargetingException $e) {
			throw new LineItemException($e->getMessage());
		} catch (\Exception $e) {
			throw new LineItemException('Line item error: ' . $e->getMessage());
		}
	}

	public function processLineItemsData($data = []) {
		$responses = [];
		$index = 0;

		$lineItemForm = new LineItemForm($data);

		list($isValid, $errorMessages) = $lineItemForm->validate();

		if (!$isValid) {
			foreach ($errorMessages as $errorMessage) {
				$responses[] = [
					'messageType' => 'danger',
					'message' => $errorMessage,
					'lineItem' => null,
					'lica' => null
				];
			}
		} else {
			$formsSet = $lineItemForm->process();

			foreach($formsSet as $alteredForm) {
				try {
					$lineItem = $this->create($alteredForm);
					$responses[$index]['lineItem'] = $lineItem;
					$responses[$index]['lica'] = $this->lineItemCreativeAssociationService->create($data['creativeId'], $lineItem['id']);
					$responses[$index]['messageType'] = 'success';
					$responses[$index]['message'] = 'Line items successfully created.';
				} catch (LineItemException $exception) {
					$responses[$index]['lineItem'] = null;
					$responses[$index]['message'] = $exception->getMessage();
					$responses[$index]['messageType'] = 'danger';
					$responses[$index]['lica'] = $this->lineItemCreativeAssociationService->getIncorrectLineItemResult();
				}

				$index++;
			}
		}

		return [
			'responses' => $responses,
			'data' => json_encode($data),
		];
	}

	public function getLineItemsInOrder($orderId) {
		$statementBuilder = new StatementBuilder();
		$statementBuilder->Where('orderId = :id and isArchived = false');
		$statementBuilder->OrderBy('id ASC');
		$statementBuilder->Limit(1000);
		$statementBuilder->WithBindVariableValue('id', $orderId);

		$page = $this->lineItemService->getLineItemsByStatement($statementBuilder->toStatement());

		return $page->getResults();
	}

	public function getLineItemById($lineItemId) {
		$statementBuilder = new StatementBuilder();
		$statementBuilder->Where('id = :id and isArchived = false');
		$statementBuilder->WithBindVariableValue('id', $lineItemId);

		$page = $this->lineItemService->getLineItemsByStatement($statementBuilder->toStatement());

		$results = $page->getResults();
		if ($results === null) {
			throw new LineItemException('Cannot find line item');
		}

		return array_shift($results);
	}

	public function addKeyValuePairToLineItemTargeting($lineItem, $keyId, $valueIds, $operator = 'IS') {
		$addedNewKeyValues = false;

		$targetingSets = $lineItem->getTargeting()->getCustomTargeting()->getChildren();

		foreach ($targetingSets as $targetingSet) {
			$keyValuePairs = $targetingSet->getChildren();
			$wasKeyInSet = false;
			foreach ($keyValuePairs as $pair) {
				if ($pair->getKeyId() === $keyId) {
					$wasKeyInSet = true;
					$pairValues = $pair->getValueIds();
					foreach ($valueIds as $valueId) {
						if (!in_array($valueId, $pairValues)) {
							$pairValues[] = $valueId;
							$addedNewKeyValues = true;
						}
					}
					$pair->setValueIds($pairValues);
					break;
				}
			}
			if (!$wasKeyInSet) {
				$keyValuePairs[] = new CustomCriteria($keyId, $valueIds, $operator);

				$addedNewKeyValues = true;
			}

			$targetingSet->setChildren($keyValuePairs);
		}

		if ($addedNewKeyValues) {
			$lineItem->setAllowOverbook( true );
			$lineItem->setSkipInventoryCheck( true );
			$this->lineItemService->updatelineItems( [ $lineItem ] );
		}
	}

	public function removeKeyValuePairFromLineItemTargeting($lineItem, $keyId, $valueIds) {
		$targetingSets = $lineItem->getTargeting()->getCustomTargeting()->getChildren();
		$newTargetingSets = [];
		$removedKeyValues = false;

		foreach ($targetingSets as $targetingSet) {
			$keyValuePairs = $targetingSet->getChildren();
			$newKeyValuePairs = [];
			foreach ($keyValuePairs as $pair) {
				if ($pair->getKeyId() === $keyId) {
					$newValues = [];
					foreach ($pair->getValueIds() as $valueId) {
						if (!in_array($valueId, $valueIds)) {
							$newValues[] = $valueId;
						}
					}
					if (count($newValues) > 0) {
						if (count($pair->getValueIds()) !== count($newValues)) {
							$removedKeyValues = true;
						}
						$pair->setValueIds($newValues);
						$newKeyValuePairs[] = $pair;
					}
				} else {
					$newKeyValuePairs[] = $pair;
				}
			}

			if (count($newKeyValuePairs) > 0) {
				if (count($targetingSet->getChildren()) !== count($newKeyValuePairs)) {
					$removedKeyValues = true;
				}
				$targetingSet->setChildren($newKeyValuePairs);
				$newTargetingSets[] = $targetingSet;
			}
		}

		if (count($lineItem->getTargeting()->getCustomTargeting()->getChildren()) !== count($newTargetingSets)) {
			$lineItem->getTargeting()->getCustomTargeting()->setChildren($newTargetingSets);
			$removedKeyValues = true;
		}

		if ($removedKeyValues) {
			$lineItem->setAllowOverbook( true );
			$lineItem->setSkipInventoryCheck( true );
			$this->lineItemService->updatelineItems( [ $lineItem ] );
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

	private function getRootAdUnit() {
		$networkService = AdManagerService::get(NetworkService::class);

		$network = $networkService->getCurrentNetwork();

		$adUnit = new AdUnitTargeting();
		$adUnit->setAdUnitId($network->getEffectiveRootAdUnitId());
		$adUnit->setIncludeDescendants(true);

		return $adUnit;
	}

	private function getCustomTargeting($form) {
		if (!isset($form['keys']) || count($form['keys']) < 1) {
			return null;
		}

		$set = new CustomCriteriaSet();
		$set->setLogicalOperator('AND');
		$targetingCriteria = [];

		$keyIds = $this->customTargetingService->getKeyIds($form['keys']);

		$countValues = count($form['values']);
		for ($i = 0; $i < $countValues; $i++) {
			$keyId = $keyIds[$i];
			$values = explode(',', $form['values'][$i]);
			$valueIds = $this->customTargetingService->getValueIds($keyId, $values);

			$criteria = new CustomCriteria();
			$criteria->setKeyId($keyId);
			$criteria->setValueIds($valueIds);
			$criteria->setOperator($form['operators'][$i]);
			$targetingCriteria[] = $criteria;
		}
		$set->setChildren($targetingCriteria);

		return $set;
	}

	private function getCreativePlaceholders($sizeList) {
		$placeholders = [];
		$sizes = explode(',', $sizeList);

		foreach ($sizes as $size) {
			list($width, $height) = explode('x', trim($size));
			$creativePlaceholder = new CreativePlaceholder();
			$creativePlaceholder->setSize(new Size(intval($width), intval($height), false));
			$placeholders[] = $creativePlaceholder;
		}

		return $placeholders;
	}

	private function setupType(LineItem $lineItem, $form) {
		$lineItem->setLineItemType($form['type']);
		$lineItem->setPriority($form['priority']);
		switch ($form['type']) {
			case 'STANDARD':
				$goal = new Goal();
				$goal->setUnits(500000);
				$goal->setUnitType('IMPRESSIONS');
				$goal->setGoalType('LIFETIME');
				$lineItem->setPrimaryGoal($goal);
				return;
			case 'PRICE_PRIORITY':
				$goal = new Goal();
				$goal->setGoalType('NONE');
				$lineItem->setPrimaryGoal($goal);
				return;
			case 'SPONSORSHIP':
			case 'NETWORK':
			case 'HOUSE':
				$goal = new Goal();
				$goal->setUnits(100);
				$lineItem->setPrimaryGoal($goal);
				return;
		}
	}

	private function setupTimeRange(LineItem $lineItem, $form) {
		if ($form['start'] !== '') {
			$lineItem->setStartDateTime(AdManagerDateTimes::fromDateTime(new \DateTime($form['start'], new \DateTimeZone('UTC'))));
		} else {
			$lineItem->setStartDateTimeType('IMMEDIATELY');
		}
		if ($form['end'] !== '') {
			$lineItem->setEndDateTime(AdManagerDateTimes::fromDateTime(new \DateTime($form['end'], new \DateTimeZone('UTC'))));
		} else {
			$lineItem->setUnlimitedEndDateTime(true);
		}
	}
}
