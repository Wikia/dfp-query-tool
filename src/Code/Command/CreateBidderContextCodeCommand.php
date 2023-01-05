<?php

namespace Code\Command;

use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreateBidderContextCodeCommand extends Command
{
    const SUPPORTED_BIDDERS = [ 'pubmatic' ];
    const CODE_TEMPLATE = <<<CODE
export function getPubmaticContext(): object {
	return {
		enabled: false,
		publisherId: '156260',
		slots: {
			[%%SLOTS_CONFIG%%]
		}
	};
}
CODE;

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
        $this->setName('code-generator:create-bidder-context-code')
            ->setDescription('Creates JavaScript code for given bidder based on given input file')
            ->addOption('bidder', 'b', InputOption::VALUE_REQUIRED, 'Name of the bidder')
            ->addOption('csv', 'f', InputOption::VALUE_REQUIRED, 'CSV file with IDs');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bidderName = $input->getOption('bidder');
        $csv = $input->getOption('csv');

        $isValid = $this->validate($output, $bidderName, $csv);

        if( $isValid ) {
            $output->writeln(sprintf('Generated context code for %s bidder:', $bidderName));
            $output->writeln(str_replace(
                '[%%SLOTS_CONFIG%%]',
                '',
                self::CODE_TEMPLATE
            ));
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
