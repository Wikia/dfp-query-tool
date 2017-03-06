<?php

namespace Report\Command;

use Knp\Command\Command;
use Report\Api\ReportService;
use Report\Database\Database;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Yaml\Yaml;

class ReportCommand extends Command
{
	private static $queriesConfig = __DIR__ . '/../../../config/queries.yml';
	private $config;
	private $database;

	public function __construct($app, $name = null) {
		parent::__construct($name);

		$this->database = new Database($app);
		$this->config = $this->getConfig();
	}

	protected function configure() {
		$this->setName('reports:fetch')
			->setDescription('Downloads data to database.')
			->addArgument(
				'queryId',
				InputArgument::OPTIONAL,
				'ID of query defined in config/queries.yml'
			)
            ->addArgument(
                'date',
                InputArgument::OPTIONAL,
                'Date of report in format YYYY-MM-DD'
            );
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$queryId = $input->getArgument('queryId');
		$notParsedDate = $input->getArgument('date');

		$queries = $this->config['queries'];
		if ($queryId !== null) {
			if (!isset($this->config['queries'][$queryId])) {
				throw new \InvalidArgumentException('Query with given ID does not exist.');
			}
			$queries = [
				$queryId => $this->config['queries'][$queryId]
			];
		}

        $date = $this->getDate($notParsedDate);
		$output->writeln("Gathering results for: {$date->format('Y-m-d')}");

		foreach ($queries as $queryId => $query) {
			$this->database->updateTable($queryId, $query);
			$output->writeln('Table updated');
			$results = $this->runQuery($queryId, $date);
			$output->writeln('Results stored');
			printf("\t- rows: %d\n", count($results));
			$this->database->insertResults($queryId, $query, $results, $date);
		}
	}

	public function runQuery($queryId, \DateTime $startDate) {
		printf("Running query: %s\n", $queryId);
		$parameters = new ParameterBag($this->config['queries'][$queryId]);
		$reportService = new ReportService();
		$results = $reportService->query($parameters, $startDate);

		return $results;
	}

	private function getConfig() {
		if (!file_exists(self::$queriesConfig)) {
			throw new \RuntimeException('Queries file does not exist (config/queries.yml).');
		}

		return Yaml::parse(file_get_contents(self::$queriesConfig));
	}

    /**
     * @param $notParsedDate
     * @return \DateTime
     */
    protected function getDate($notParsedDate): \DateTime {
        $notParsedDate = $notParsedDate ?: '-1 day';
        $date = new \DateTime($notParsedDate, new \DateTimeZone('Europe/Warsaw'));
        $date->setTime(0, 0, 0);

        return $date;
    }
}
