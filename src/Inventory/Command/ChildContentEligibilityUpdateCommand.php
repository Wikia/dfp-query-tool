<?php

namespace Inventory\Command;

use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChildContentEligibilityUpdateCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('line-items:child-content-eligibility:update')
			->setDescription('Update Child Content Eligibility field in given order')
			->addArgument('ids', InputArgument::REQUIRED, 'Order ids (separated with comma');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$lineItemService = new LineItemService();
		$ids = explode(',', $input->getArgument('ids'));

		$counterAllUpdated = 0;
		$counterAllSkipped = 0;
		$counterAllError = 0;

		foreach ($ids as $id) {
			printf("========== Processing Order ID: %s ==========\n", $id);

			$lineItems = $lineItemService->getLineItemsInOrder($id);
			$counter = 1;
			$counterUpdated = 0;
			$counterSkipped = 0;
			$counterError = 0;
			$count = count($lineItems);

			foreach ($lineItems as $lineItem) {
				try {
					if ($lineItem->getChildContentEligibility() !== 'ALLOWED') {
						$lineItemService->setChildContentEligibility($lineItem, true);

						printf("[%s of %s processed] ID %s updated: %s\n", $counter, $count, $lineItem->getId(), $lineItem->getName());

						$counterUpdated++;
					} else {
						printf("[%s of %s processed] ID %s skipped: %s\n", $counter, $count, $lineItem->getId(), $lineItem->getName());

						$counterSkipped++;
					}
				} catch (\Exception $e) {
					printf("[%s of %s processed] ID %s error: %s\n", $counter, $count, $lineItem->getId(), $lineItem->getName());

					$counterError++;
				}

				$counter++;
			}

			printf("========== Order ID: %s - processed ==========\n", $id);
			printf("- Updated: %s\n", $counterUpdated);
			printf("- Skipped: %s\n", $counterSkipped);
			printf("- Error: %s\n\n\n", $counterError);

			$counterAllUpdated += $counterUpdated;
			$counterAllSkipped += $counterSkipped;
			$counterAllError += $counterError;
		}

		printf("========== All Orders processed ==========\n");
		printf("- Total updated: %s\n", $counterAllUpdated);
		printf("- Total skipped: %s\n", $counterAllSkipped);
		printf("- Total error: %s\n\n", $counterAllError);
	}
}
