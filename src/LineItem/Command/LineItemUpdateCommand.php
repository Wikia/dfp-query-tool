<?php

namespace LineItem\Command;

use Inventory\Api\LineItemService;
use Google\AdsApi\AdManager\v202408\ApiException;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LineItemUpdateCommand extends Command
{
	public function __construct($app, $name = null)
	{
		parent::__construct($name);
	}

	protected function configure()
	{
		$this->setName('line-items:update')
			->setDescription('Accepts CSV of line item ids to update a key value (non-custom targeting)')
			->addOption('key', 'k', InputOption::VALUE_REQUIRED, 'Targeting key')
			->addOption('value', 'l', InputOption::VALUE_REQUIRED, 'Value')
            ->addOption('csv', 'f', InputOption::VALUE_REQUIRED, 'CSV file with IDs')
            ->addOption('skip-first-row', 'r', InputOption::VALUE_OPTIONAL, 'Skip first row (default: true) - most of the times these are headers', true);
	}

	protected function execute(InputInterface $input, OutputInterface $output)
    {
        $csv = $input->getOption('csv');

        if ( is_file($csv) ) {
            $key = $input->getOption('key');
            $value = $input->getOption('value');
            $skipFirstRow = $input->getOption('skip-first-row');

            $content = $this->getCsvContentAsArray($csv, $skipFirstRow);

            if ( !empty($content) ) {
                $lineItemService = new LineItemService();

                try {
                    $lineItems = $lineItemService->getLineItemsByIds($content);
                }
                catch (ApiException $e) {
                    $output->writeln($e->getMessage());
                    return;
                }

                if ( !empty($lineItems) ) {
                    $ids = [];
                    foreach ( $lineItems as $lineItem ) {
                        $ids[] = $lineItem->getId();
                        switch ( $key ) {
                            case 'priority':
                                $lineItem->setPriority($value);
                                break;
                        }
                    }

                    try {
                        $lineItemService->update($lineItems);
                    }
                    catch (ApiException $e) {
                        $output->writeln($e->getMessage());
                        return;
                    }

                    $output->writeln('Updated line items with ids: ' . implode(', ', $ids));
                }
                else {
                    $output->writeln('No line items returned from the API');
                }
            }
            else {
                $output->writeln('No line item ids found in the CSV file');
            }
        }
        else {
            $output->writeln('File does not exist!');
        }
    }

    private function getCsvContentAsArray($csv, $skipFirstRow): array 
    {
        $file = fopen($csv, 'r');
                
        $data = [];
        while ( ($row = fgetcsv($file)) !== FALSE ) {
            if ($skipFirstRow) {
                $skipFirstRow = false;
                continue;
            }
            $data[] = $row[0];
        }
        
        fclose($file);

        return $data;
    }
}

