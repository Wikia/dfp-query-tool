<?php

namespace Inventory\Command;

use Inventory\Api\SuggestedAdUnitsService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SuggestedAdUnitsCommand extends Command
{
	public function __construct($app, $name = null) {
		parent::__construct($name);
	}

	protected function configure() {
		$this->setName('suggested-adunits:approve')
			->setDescription('Approve all suggested ad units in queue.');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$suggestedAdUnitsService = new SuggestedAdUnitsService();

		$suggestedAdUnitsService->approve();
	}
}
