<?php

namespace Code\Command;

use Knp\Command\Command;
use phpDocumentor\Reflection\File;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateBidderContextFileCommand extends Command
{
    const SUPPORTED_BIDDERS = [ 'pubmatic' ];

    public function __construct($app, $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('code:create-bidder-context-code')
            ->setDescription('Creates JavaScript code for given bidder based on given input file')
            ->addOption('bidder', 'b', InputOption::VALUE_REQUIRED, 'Name of the bidder')
            ->addOption('csv', 'f', InputOption::VALUE_REQUIRED, 'CSV file with IDs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bidderName = $input->getOption('bidder');
        $csv = $input->getOption('csv');

        $isValid = $this->validate($output, $bidderName, $csv);

        if( $isValid ){
            $output->writeln('Generating the context code...' );
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
}
