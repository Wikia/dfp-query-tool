<?php

namespace LineItem\Command;

use Inventory\Api\CustomTargetingService;
use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LineItemKeyValuesRemoveCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('line-items:key-values:remove')
			->setDescription('Remove key-values pair from line item custom targeting')
			->addArgument('line-item-id', InputArgument::REQUIRED, 'Line item ID')
			->addArgument('key', InputArgument::REQUIRED, 'Key')
			->addArgument('values', InputArgument::REQUIRED, 'Values (separated with comma)');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$customTargetingService = new CustomTargetingService();
		$lineItemService = new LineItemService();

		$lineItemId = $input->getArgument('line-item-id');
		$key = $input->getArgument('key');
		$values = explode(',', $input->getArgument('values'));

		$lineItem = $lineItemService->getLineItemById($lineItemId);
		$keyId = array_shift($customTargetingService->getKeyIds([$key]));
		$valueIds = $customTargetingService->getValueIds($keyId, $values);

		$lineItemService->removeKeyValuePairFromLineItemTargeting($lineItem, $keyId, $valueIds);

		printf("Line item %s updated\n", $lineItem->getId());
	}
}
