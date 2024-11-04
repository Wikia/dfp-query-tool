<?php

namespace Creative\Command;

use Inventory\Api\CreativeService;
use Inventory\Api\LineItemService;
use Inventory\Api\LineItemCreativeAssociationService;
use Inventory\Api\OrderService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OverrideCreativeSizesInOrderLineItems extends Command
{
	public function __construct($app, string $name = null ) {
		parent::__construct( $name );
	}

	protected function configure()
	{
		$this->setName('order:override-creative-sizes')
			->setDescription('Override creative sizes in all line items in order')
			->addOption(
				'order',
				'o',
				InputOption::VALUE_REQUIRED,
				'Order ID'
			)->addOption(
				'sizes',
				's',
				InputOption::VALUE_REQUIRED,
				'Sizes override (width\'x\'height, separated with comma)'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$orderId = $input->getOption( 'order' );
		$sizes = $input->getOption( 'sizes' );

		printf("Order ID: %s\n", $orderId);
		printf("Sizes: %s\n", $sizes);

		$lineItemService = new LineItemService();
		$lineItemCreativeAssociationService = new LineItemCreativeAssociationService();

		$lineItemsInOrder = $lineItemService->getLineItemsInOrder($orderId);

		if ($lineItemsInOrder !== null) {
			printf("Found %s lineItems in order %s\n", count($lineItemsInOrder), $orderId);
			foreach ( $lineItemsInOrder as $lineItem ) {
				$lineItemId = $lineItem->getId();
				$lineItemCreativeAssociationService->overrideAllLineItemCreativeSizes( $lineItemId, $sizes );
			}
		} else {
			printf("No line items found in order %s\n", $orderId);
		}
	}
}
