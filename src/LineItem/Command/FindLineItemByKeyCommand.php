<?php

namespace LineItem\Command;

use Inventory\Api\CustomTargetingService;
use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FindLineItemByKeyCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('line-items:find-by-key')
			->setDescription('Find line items by used keys in the targeting')
			->addArgument('keys', InputArgument::REQUIRED, 'Keys (separated with comma')
			->addOption(
				'--exclude-inactive',
				null,
				InputArgument::OPTIONAL,
				'Include only active line items.',
				false
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$customTargetingService = new CustomTargetingService();
		$lineItemService = new LineItemService();

		$keys = explode(',', $input->getArgument('keys'));
		$excludeInactive = $input->getOption('exclude-inactive');

		$keyIds = $customTargetingService->getKeyIds($keys);

		$lineItems = $lineItemService->findLineItemIdsByKeys($keyIds, $excludeInactive);

		if (empty($lineItems)) {
			printf( "No line-items found for given keys" );
			return;
		}

		foreach ($lineItems as $lineItem) {
			printf(" - order: %s, line item: %s\n", $lineItem['order_id'], $lineItem['line_item_id']);
		}
	}
}

