<?php

namespace LineItem\Command;

use Inventory\Api\CustomTargetingService;
use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LineItemKeyValuesAddCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('line-items:key-values:add')
			->setDescription('Add key-values pair to line item custom targeting')
			->addArgument('line-item-id', InputArgument::REQUIRED, 'Line item ID')
			->addArgument('key', InputArgument::REQUIRED, 'Key')
			->addArgument('values', InputArgument::REQUIRED, 'Values (separated with comma)')
			->addArgument('operator', InputArgument::OPTIONAL, 'Key-val operator (IS or IS NOT)', 'IS');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$customTargetingService = new CustomTargetingService();
		$lineItemService = new LineItemService();

		$lineItemId = $input->getArgument('line-item-id');
		$key = $input->getArgument('key');
		$values = explode(',', $input->getArgument('values'));
		$operator = $input->getArgument('operator');

		$lineItem = $lineItemService->getLineItemById($lineItemId);
		$keyIds = $customTargetingService->getKeyIds([$key]);
		$keyId = array_shift($keyIds);
		$valueIds = $customTargetingService->getValueIds($keyId, $values);

		$lineItemService->addKeyValuePairToLineItemTargeting($lineItem, $keyId, $valueIds, $operator);

		printf("Line item %s updated\n", $lineItem->getId());
	}
}
