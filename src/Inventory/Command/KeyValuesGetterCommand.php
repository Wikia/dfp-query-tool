<?php

namespace Inventory\Command;

use Inventory\Api\CustomTargetingService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class KeyValuesGetterCommand extends Command
{
	public function __construct($app, $name = null) {
		parent::__construct($name);
	}

	protected function configure() {
		$this->setName('key-values:get')
			->setDescription('Get GAM ids of given key and its values')
			->addArgument('key', InputArgument::REQUIRED, 'Name of key')
			->addArgument('values', InputArgument::REQUIRED, 'Values (separated with comma)');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$key = $input->getArgument('key');
		$values = explode(',', $input->getArgument('values'));

		$targetingService = new CustomTargetingService();
		$keyId = array_shift($targetingService->getKeyIds([$key]));

		printf("%s (%s) values:\n", $key, $keyId);
		$valueIds = $targetingService->getValueIds($keyId, $values);

		foreach ($values as $key => $value) {
			printf("  - %s: %s\n", $value, $valueIds[$key]);
		}
	}
}
