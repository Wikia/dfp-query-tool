<?php

namespace Report\Command;

use Knp\Command\Command;
use Report\Api\ReportService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Yaml\Yaml;

class ReportCommand extends Command
{
	private static $queriesConfig = __DIR__ . '/../../../config/queries.yml';
	private $config;

	public function __construct($name = null) {
		parent::__construct($name);

		$this->config = $this->getConfig();
	}

	protected function configure() {
		$this->setName('reports:fetch')
			->setDescription('Downloads data to database.')
			->addArgument(
				'queryId',
				InputArgument::OPTIONAL,
				'ID of query defined in config/queries.yml'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$queryId = $input->getArgument('queryId');

		$queries = $this->config['queries'];
		if ($queryId !== null) {
			if (!isset($this->config['queries'][$queryId])) {
				throw new \InvalidArgumentException('Query with given ID does not exist.');
			}
			$queries = [
				$queryId => $this->config['queries'][$queryId]
			];
		}

		foreach ($queries as $queryId => $query) {
			$results = $this->runQuery($queryId);
			printf("\t- rows: %d\n", count($results));
		}
	}

	public function runQuery($queryId) {
		printf("Running query: %s\n", $queryId);
		$parameters = new ParameterBag($this->config['queries'][$queryId]);
		$reportService = new ReportService();

		$results = $reportService->query($parameters);

		return $results;
	}

	private function getConfig() {
		if (!file_exists(self::$queriesConfig)) {
			throw new \RuntimeException('Queries file does not exist (config/queries.yml).');
		}

		return Yaml::parse(file_get_contents(self::$queriesConfig));
	}
}
