<?php

namespace Inventory\Command;

use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RetargetLineItemsInOrderCommand extends Command
{
	public function __construct($app, $name = null) {
		parent::__construct($name);
	}

	protected function configure() {
		$this->setName('retargtet-line-items-in-order:src')
			->setDescription('Add src=test to all line items in the order.')
			->addArgument('order_id', InputArgument::REQUIRED, 'Order ID to fetch update line items');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$lineItemService = new LineItemService();

		foreach ($lineItemService->getLineItemsInOrder($input->getArgument('order_id')) as $lineItem) {
			$lineItemService->addSrcTestTargeting($lineItem);
		}
	}
}
