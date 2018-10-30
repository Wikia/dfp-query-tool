<?php

namespace LineItem\Command;

use Inventory\Api\CustomTargetingService;
use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderLineItemsKeyValuesRemoveCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('order:key-values:remove')
			->setDescription('Remove key-values pair from all line item custom targeting in order')
			->addArgument('order-id', InputArgument::REQUIRED, 'Order ID')
			->addArgument('key', InputArgument::REQUIRED, 'Key')
			->addArgument('values', InputArgument::REQUIRED, 'Values (separated with comma)');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$customTargetingService = new CustomTargetingService();
		$lineItemService = new LineItemService();

		$orderId = $input->getArgument('order-id');
		$key = $input->getArgument('key');
		$values = explode(',', $input->getArgument('values'));

		$lineItems = $lineItemService->getLineItemsInOrder($orderId);
		$count = count($lineItems);
		$keyId = array_shift($customTargetingService->getKeyIds([$key]));
		$valueIds = $customTargetingService->getValueIds($keyId, $values);

		printf("Updating %s line items\n", $count);
		foreach ($lineItems as $i => $lineItem) {
			$lineItemService->removeKeyValuePairFromLineItemTargeting($lineItem, $keyId, $valueIds);
			printf("  - Line item %s updated (%s/%s)\n", $lineItem->getId(), $i + 1, $count);
		}

		printf("Order updated\n");
	}
}
