<?php

namespace Creative\Command;

use Inventory\Api\CreativeService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreativeCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('creatives:find')
			->setDescription('Finds all creatives with given text in snippet code.')
			->addArgument(
				'output',
				InputArgument::REQUIRED,
				'Output file path'
			)
			->addOption(
				'code-fragment',
				'c',
				InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
				'Fragment of code'
			)
			->addOption(
				'type',
				't',
				InputOption::VALUE_OPTIONAL,
				'Type of creative'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$type = $input->getOption('type');
		$fragments = $input->getOption('code-fragment');
		$output = $input->getArgument('output');
		if (count($fragments) === 0) {
			printf('Missing argument. Define code fragments.');
			exit;
		}

		$this->findCreatives($output, $fragments, $type);
	}

	public function findCreatives($output, $fragments = [], $type = null)
	{
		$creativeService = new CreativeService();

		if ($type === 'CreativeTemplate') {
			$results = $creativeService->findCreativeTemplates($output, $fragments);
		} else {
			$results = $creativeService->find($output, $fragments);
		}

		return $results;
	}
}
