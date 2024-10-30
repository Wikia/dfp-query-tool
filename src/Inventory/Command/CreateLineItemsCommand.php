<?php

namespace Inventory\Command;

use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateLineItemsCommand extends Command {
	public function __construct( $app, $name = null ) {
		parent::__construct( $name );
	}

	protected function configure() {
		$this->setName( 'line-items:create' )
			->setDescription( 'Creates line items in the order (with associated creative)' )
			->addArgument(
				'input',
				InputArgument::REQUIRED,
				'JSON file with line items configuration'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$input = $input->getArgument('input');
		$data = json_decode(file_get_contents($input), true);

		$iteratorDetails = explode( ',', $data[ 'iterator' ] );
		$priceMapDetails = explode( ',', $data[ 'priceMap' ] );

		foreach ( $data as $lineItemKey => $lineItemValue ) {
			printf( "ParsedLineItem details - key: \"%s\" \n value: %s \n", $lineItemKey, print_r( $lineItemValue, true ) );
		}

		if ( $priceMapDetails && $iteratorDetails ) {
			$iteratorToPriceMap = [];
			printf( "Lining up iterator to priceMap items.\n" );
			foreach ( $iteratorDetails as $iteratorKey => $iteratorValue ) {
				$iteratorToPriceMap[ $iteratorKey ] = [$iteratorValue, $priceMapDetails[ $iteratorKey ]];
				printf( "Iterator to priceMap details: %s \n", print_r( $iteratorToPriceMap[ $iteratorKey ], true ) );
			}
		} else {
			printf( "PriceMap details not provided. Skipping priceMap details.\n" );
		}

//		printf( "=== Lines creation - Dry run stopping point. Will not send line item creation requests to GAM.  ===\n" );
//		return;

		$lineItemsService = new LineItemService();

		$result = $lineItemsService->processLineItemsData( $data );

		printf( "=== Lines creation summary ===\n" );

		$successes = 0;
		foreach ( $result[ 'responses' ] as $response ) {
			if ( $response[ 'messageType' ] === 'success' ) {
				$successes++;
			} else {
				printf( "Error creating line: %s\n", $response[ 'message' ] );
			}
		}

		printf( "%s/%s line items created successfully\n", $successes, count( $result[ 'responses' ] ) );
	}
}
