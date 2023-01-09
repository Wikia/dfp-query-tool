<?php

namespace Code\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBiddersSlotsJsonCommand extends Command
{
    const SUPPORTED_BIDDERS = [ 'pubmatic' ];

    /**
     * top_boxad: {
     *   sizes: [
     *     [300, 250],
     *     [300, 600],
     *   ],
     *   ids: ['/5441/TOP_RIGHT_BOXAD_300x250@300x250', '/5441/TOP_RIGHT_BOXAD_300x600@300x600'],
     * },
     */
    const SLOT_TEMPLATE = <<<CODE
[%%SLOT_NAME%%]: {
    sizes: [
        [%%SLOT_SIZES%%]
    ],
    ids: [[%%SLOT_IDS%%]],
},
CODE;

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
            ->addOption( 'separator', 's', InputOption::VALUE_OPTIONAL, 'CSV file separator (default: ',')', ',');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bidderName = $input->getOption('bidder');
        $csvFile = $input->getOption('csv');
        $csvSeparator = $input->getOption('separator');

        $isValid = $this->validate($output, $bidderName, $csvFile);

        if( $isValid ) {
            $csv = $this->getCsvContentAsArray($csvFile, $csvSeparator);
            $output->writeln(sprintf('Generated slots JSON for %s bidder:', $bidderName));
            print_r($csv);
        }
    }

    private function validate($output, $bidderName, $csv) {
        if ( !in_array($bidderName, self::SUPPORTED_BIDDERS) ) {
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
}
