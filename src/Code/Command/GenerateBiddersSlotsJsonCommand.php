<?php

namespace Code\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBiddersSlotsJsonCommand extends Command
{
    const SUPPORTED_BIDDERS = [ 'appnexus', 'magnite', 'pubmatic', 'medianet' ];
    protected string $selectedBidder;

    public function __construct($app, $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('generate:bidders-slots-json')
            ->setDescription('Creates JSON for given bidder based on given input file')
            ->addOption('bidder', 'b', InputOption::VALUE_REQUIRED, 'Name of the bidder')
            ->addOption('csv', 'f', InputOption::VALUE_REQUIRED, 'CSV file with IDs')
            ->addOption( 'separator', 's', InputOption::VALUE_OPTIONAL, 'CSV file separator (default: \';\')', ';')
            ->addOption( 'skip-first-row', 'r', InputOption::VALUE_OPTIONAL, 'Skip first row (default: true) - most of the times these are headers', true)
            ->addOption( 'pretty-print', 'p', InputOption::VALUE_OPTIONAL, 'Should the JSON output be pretty-printed (default: true)', true);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->selectedBidder = $input->getOption('bidder');
        $csvFile = $input->getOption('csv');
        $csvSeparator = $input->getOption('separator');
        $skipFirstRow = !($input->getOption('skip-first-row') === 'false');
        $prettyPrint = !($input->getOption('pretty-print') === 'false');

        $isValid = $this->validate($output, $csvFile);

        if( $isValid ) {
            $csv = $this->getCsvContentAsArray($csvFile, $csvSeparator);
            $output->writeln(sprintf('Generated JSON for %s bidder:', $this->selectedBidder));
            $output->writeln($this->generateJSON($csv, $skipFirstRow, $prettyPrint));
        }
    }

    private function validate($output, $csv) {
        if ( !in_array($this->selectedBidder, self::SUPPORTED_BIDDERS) ) {
            $output->writeln('Invalid bidder name!');
            return false;
        }

        if ( !file_exists($csv) ) {
            $output->writeln('File does not exist!');
            return false;
        }

        return true;
    }

    private function getCsvContentAsArray($csvFile, $csvSeparator): array {
        return array_map( function($row) use ($csvSeparator) {
            return str_getcsv($row, $csvSeparator);
        }, file($csvFile));
    }

    private function generateJSON(array $csvContent, bool $skipFirstRow, bool $prettyPrint): string {
        $generator = $this->getGenerator();

        foreach($csvContent as $rowNo => $row) {
            if ($skipFirstRow && $rowNo === 0 ) {
                continue;
            }

            $slotName = $row[0];
            $slotSizes = [(int) $row[1], (int) $row[2]];
            $slotBidderId = $row[3];

            $generator->updateAfterRowIteration($slotName, $slotSizes, $slotBidderId, $row);
        }
        $generator->updateAfterLoop();

        return json_encode($generator->getBidderConfig(), $prettyPrint ? JSON_PRETTY_PRINT : 0);
    }

    private function getGenerator(): PrebidSlotConfigGenerator {
        switch($this->selectedBidder) {
            case 'appnexus':
                return new AppNexusSlotConfigGenerator();
            case 'pubmatic':
                return new PubmaticSlotConfigGenerator();
            case 'magnite':
                return new MagniteSlotConfigGenerator();
            case 'medianet':
                return new MedianetSlotConfigGenerator();
            default:
                throw new \Exception('Unknown bidder slot config generator');
        }
    }
}
