<?php

namespace LineItem\Command;

use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LineItemRenameCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('line-items:rename')
			->setDescription('Rename all lines in given orders')
			->addArgument('ids', InputArgument::REQUIRED, 'Order ids (separated with comma')
			->addArgument('find', InputArgument::REQUIRED, 'Part of name to replace')
			->addArgument('replace', InputArgument::REQUIRED, 'Text that will be used in replace');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$lineItemService = new LineItemService();
		$ids = explode(',', $input->getArgument('ids'));

		$counterAllUpdated = 0;
		$counterAllError = 0;

		foreach ($ids as $id) {
			printf("========== Processing Order ID: %s ==========\n", $id);

			$lineItems = $lineItemService->getLineItemsInOrder($id);
			$counter = 1;
			$counterUpdated = 0;
			$counterError = 0;
			$count = count($lineItems);

			foreach ($lineItems as $lineItem) {
				try {
					$lineItemService->replaceInName($lineItem, $input->getArgument('find'), $input->getArgument('replace'));

					printf("[%s of %s processed] ID %s updated: %s\n", $counter, $count, $lineItem->getId(), $lineItem->getName());

					$counterUpdated++;
				} catch (\Exception $e) {
					printf("[%s of %s processed] ID %s error: %s\n", $counter, $count, $lineItem->getId(), $lineItem->getName());

					$counterError++;
				}

				$counter++;
			}

			printf("========== Order ID: %s - processed ==========\n", $id);
			printf("- Updated: %s\n", $counterUpdated);
			printf("- Error: %s\n\n\n", $counterError);

			$counterAllUpdated += $counterUpdated;
			$counterAllError += $counterError;
		}

		printf("========== All Orders processed ==========\n");
		printf("- Total updated: %s\n", $counterAllUpdated);
		printf("- Total error: %s\n\n", $counterAllError);
	}
}
