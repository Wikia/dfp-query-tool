<?php

namespace Inventory\Command;

use Inventory\Api\AdUnitsService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ArchiveAdUnitsCommand extends Command
{
	public function __construct($app, $name = null) {
		parent::__construct($name);
	}

	protected function configure() {
		$this->setName('adunits:archive')
			->setDescription('Archive ad units.')
			->addArgument('adUnitCodes', InputArgument::REQUIRED, 'Ad unit code (separated with comma)');
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$adUnitsService = new AdUnitsService();
		$adUnitCodes = $input->getArgument('adUnitCodes');

		foreach (explode(',', $adUnitCodes) as $adUnitCode) {
			$adUnitsService->archive($adUnitCode);
		}
	}
}
