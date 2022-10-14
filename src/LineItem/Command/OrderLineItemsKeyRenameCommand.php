<?php

namespace LineItem\Command;

use Inventory\Api\CustomTargetingService;
use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OrderLineItemsKeyRenameCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('order:key-values:rename-key')
			->setDescription('Renames key in all line items custom targeting in order')
			->addArgument('order-id', InputArgument::REQUIRED, 'Order ID')
			->addArgument('old-key', InputArgument::REQUIRED, 'Old key')
			->addArgument('new-key', InputArgument::REQUIRED, 'New key');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$customTargetingService = new CustomTargetingService();
		$lineItemService = new LineItemService();

		$orderId = $input->getArgument('order-id');
		$oldKeyName = substr($input->getArgument('old-key'), 0, 20);
		$newKeyName = substr($input->getArgument('new-key'), 0, 20);

		list($oldKeyId, $newKeyId) = array_values($customTargetingService->getKeyIds([$oldKeyName, $newKeyName]));
		$lineItems = $lineItemService->getLineItemsInOrder($orderId);

		foreach ($lineItems as $i => $lineItem) {
			$lineItemService->renameKeyInLineItemTargeting($lineItem, $oldKeyId, $newKeyId);

			printf('.');

			if (($i+1) % 50 === 0) {
				printf(" (%s/%s)\n", $i+1, count($lineItems));
			}
		}
	}
}
