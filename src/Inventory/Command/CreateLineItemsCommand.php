<?php

namespace Inventory\Command;

use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateLineItemsCommand extends Command
{
	public function __construct($app, $name = null) {
		parent::__construct($name);
	}

	protected function configure() {
		$this->setName('line-items:create')
			->setDescription('Creates line items in the order (with associated creative)')
			->addArgument('input', InputArgument::REQUIRED, 'JSON file with line items configuration');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$input = $input->getArgument('input');
		$data = json_decode(file_get_contents($input), true);

		$lineItemsService = new LineItemService();
		$result = $lineItemsService->processLineItemsData($data);

		$successes = 0;
		foreach ($result['responses'] as $response) {
			if ($response['messageType'] === 'success') {
				$successes++;
			}
		}

		printf("%s/%s line items created successfully", $successes, count($result['responses']));
	}
}
