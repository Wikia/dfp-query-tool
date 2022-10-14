<?php

namespace LineItem\Command;

use Inventory\Api\CustomTargetingService;
use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderLineItemsKeyValuesAddCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('order:key-values:add')
			->setDescription('Add key-values pair to all line items custom targeting in order')
			->addArgument('order-id', InputArgument::REQUIRED, 'Order ID')
			->addArgument('key', InputArgument::REQUIRED, 'Key')
			->addArgument('values', InputArgument::REQUIRED, 'Values (separated with comma)')
			->addArgument('operator', InputArgument::OPTIONAL, 'Key-val operator (IS or IS NOT)', 'IS');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$customTargetingService = new CustomTargetingService();
		$lineItemService = new LineItemService();

		$orderId = $input->getArgument('order-id');
		$key = $input->getArgument('key');
		$values = explode(',', $input->getArgument('values'));
		$operator = $input->getArgument('operator');

		$lineItems = $lineItemService->getLineItemsInOrder($orderId);
		$count = count($lineItems);
		$keyIds = $customTargetingService->getKeyIds([$key]);
		$keyId = array_shift($keyIds);
		$valueIds = $customTargetingService->getValueIds($keyId, $values);

		printf("Updating %s line items\n", $count);
		foreach ($lineItems as $i => $lineItem) {
			$lineItemService->addKeyValuePairToLineItemTargeting($lineItem, $keyId, $valueIds, $operator);
			printf("  - Line item %s updated (%s/%s)\n", $lineItem->getId(), $i + 1, $count);
		}

		printf("Order updated\n");
	}
}
