<?php

namespace Inventory\Command;

use Inventory\Api\CustomTargetingService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeyValuesCreateCommand extends Command
{
	public function __construct($app, $name = null) {
		parent::__construct($name);
	}

	protected function configure() {
		$this->setName('key-values:create')
			->setDescription('Create GAM key and its values')
			->addArgument('key', InputArgument::REQUIRED, 'Name of key')
			->addArgument('values', InputArgument::OPTIONAL, 'Values (separated with comma)', null);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$key = $input->getArgument('key');

		$targetingService = new CustomTargetingService();
		if ($input->getArgument('values') === null) {
			$targetingService->createKey($key);
		} else {
			$values = explode(',', $input->getArgument('values'));

			$targetingService->createKey($key, $values);
		}
	}
}
