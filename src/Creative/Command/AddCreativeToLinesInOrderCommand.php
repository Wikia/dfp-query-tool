<?php

namespace Creative\Command;


use Inventory\Api\CreativeService;
use Inventory\Api\LineItemService;
use Inventory\Api\LineItemCreativeAssociationService;
use Inventory\Api\OrderService;
use Knp\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Google\AdsApi\AdManager\v202408\LineItem;

class AddCreativeToLinesInOrderCommand extends Command
{
    public function __construct($app, $name = null)
    {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('order:add-creatives')
            ->setDescription('Finds all line-items in given order, create and adds creative to them.')
            ->addOption(
                'order',
                'o',
                InputOption::VALUE_REQUIRED,
                'Order id'
            )
            ->addOption(
                'creative-template',
                'c',
                InputOption::VALUE_REQUIRED,
                'Creative template id'
            )
			->addOption(
				'override-creative-size',
				'ocs',
				InputOption::VALUE_REQUIRED,
				"Overrides the creative size when its being created.
				The creative sizes should be in the format of 'width'x'height', separated by a comma.
				For example, '300x250,728x90', will create creatives with sizes 300x250 and 728x90 as the overrides.
				One thing to note is that the override size on the creative has to be a subset of the line item sizes.
				For example, if you put a 1x1 size on the creative, but the line item that'll be associated with it has a 300x250, 300x1050 etc sizes 
				(but not a 1x1), the creative will not be associated with the line item.
				https://developers.google.com/ad-manager/api/reference/v202405/LineItemCreativeAssociationService
				
				This option will only be activated if the 'force-new-creative' or '-f' option is also added."
			)
			->addOption(
				'multiple-creatives-per-line-item',
				'm',
				InputOption::VALUE_REQUIRED,
				"Allows the creation of 'x' amount of creatives per line item.
				Adding in a value of 5, will create 5 new creatives per line item in the order.
				This option will only be activated if the 'force-new-creative' or '-f' option is also added."
			)
            ->addOption(
                'creative-variables',
                'r',
                InputOption::VALUE_OPTIONAL,
                "Creative's variables in a specific form, for example: var1:val1;var2:val2;var3:val3 ..."
            )
            ->addOption(
                'creative-suffix',
                's',
                InputOption::VALUE_OPTIONAL,
                "Creative's name suffix"
            )
            ->addOption(
                'force-new-creative',
                'f',
                InputOption::VALUE_OPTIONAL,
                "Force creating new creative per line item"
            )
			->addOption(
				'append-loop-index',
				'a',
				InputOption::VALUE_OPTIONAL,
				'Appends the loop index at the end of the creative\'s name.
				The loop index will get added after the "creative-suffix"" option.
				The loop index will be wrapped with parentheses, so a loop index of 1 will be (1).'
			)
			->addOption(
			'offset-creative-name-loop-index',
			'ocn',
			InputOption::VALUE_REQUIRED,
			'This option can only be activated if the "append-loop-index" option is also added.
			This will add the offset amount to the creative name\'s loop index. For example, if you have 2 creatives to add 
			to a line item that has 5 existing creatives, adding an offset of 5 will add two new creatives to this line item
			that will have indices of (6) and (7) added to them respectively. This option allows for adding additional creatives
			without disturbing the naming order of the existing creatives.'
			);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orderId = $input->getOption('order');
        $creativeTemplateId = $input->getOption('creative-template');
        $creativeVariables = $input->getOption('creative-variables');
        $overrideCreativeSize = $input->getOption('override-creative-size');
		$multipleCreativesPerLineItem = intval($input->getOption('multiple-creatives-per-line-item')) ?? 1;
        $creativeNameSuffix = $input->getOption('creative-suffix');
        $forceNewCreative = !!$input->getOption('force-new-creative');
        $appendLoopIndex = $input->getOption('append-loop-index');
        $offsetCreativeNameLoopIndex = $input->getOption('offset-creative-name-loop-index') ?? 0;

        if ( is_null($orderId) || intval($orderId) === 0 ) {
            throw new InvalidOptionException('Invalid order ID');
        }

        if ($multipleCreativesPerLineItem < 1) {
			throw new InvalidOptionException(
				'The option "multiple-creatives-per-line-item" must be a positive integer.
				You need to have at least 1 creative attached per line item.'
			);
		}

        if (!$forceNewCreative && ($overrideCreativeSize || $multipleCreativesPerLineItem)) {
			throw new InvalidOptionException('The options "override-creative-size" and "multiple-creatives-per-line-item" can only be used with the "force-new-creative" option.');
		}

        if (!$appendLoopIndex && $offsetCreativeNameLoopIndex) {
			throw new InvalidOptionException('The option "offset-creative-name-loop-index" can only be used with the "append-loop-index" option.');
		}

        if (!is_numeric($offsetCreativeNameLoopIndex) || $offsetCreativeNameLoopIndex < 0) {
        	throw new InvalidOptionException('The option "offset-creative-name-loop-index" must be a positive integer.');
		}

        if ( is_null($creativeTemplateId) || intval($creativeTemplateId) === 0 ) {
            throw new InvalidOptionException('Invalid creative template ID');
        }

        if ( !empty($creativeVariables) ) {
            $creativeVariables = $this->parseCreativeVariables($creativeVariables);
        }

        printf("Order ID: %d\n", $orderId);
        printf("Creative template ID: %d\n", $creativeTemplateId);
        printf("Force new creative: %s\n", $forceNewCreative ? "true" : "false");
        printf("Override Creative sizes: %s\n", $overrideCreativeSize);
        printf("How many creatives will be added per lineItem? %s", ($multipleCreativesPerLineItem ?: 1));

        $orderService = new OrderService();
        $lineItemService = new LineItemService();
        $creativeService = new CreativeService();
        $lineItemCreativeAssociationService = new LineItemCreativeAssociationService();

        $order = $orderService->getById($orderId);
        $creativeForm = [
            'advertiserId' => $order->getAdvertiserId(),
            'creativeTemplateId' => $creativeTemplateId,
            'variables' => $creativeVariables
        ];

        $lineItems = $lineItemService->getLineItemsInOrder($orderId);

        // Debug only
//		$singleLineItem = null;
//		$lineItemId = null;

/*        foreach($lineItems as $lineItem => $lineItemValue) {
			$lineItemId = (fn() => $this->id)->call($lineItemValue);
        	if ($lineItemId === 6799123339) {
				$singleLineItem = $lineItemValue;
				printf("Debug for lineItem Id 6799123339:\n%s\n", print_r($lineItemValue, true));
			}
		}*/

//      printf("Reprinting single lineItem for debugging:\n%s\n", print_r($singleLineItem, true));

//		printf("First lineItem entry: %s", print_r($lineItems[0], true));
//		printf("First lineItem's id: %s", (fn() => $this->id)->call($lineItems[0]));


        $createdCreatives = [];
        $lineItemsWithNewCreatives = [];
        $failedLineItems = [];
        $errorMsgs = [];
        $creativeId = null;

        printf("Adding creatives to %s line item(s)\n", count($lineItems));
//        return;

		// 138250147348 - this one is 300x250
//		$testCreativeIds =  array(138494323652, 138494911660, 138494911381, 138494051340, 138250147348);
//		$testCreativeIds =  array(138250147348);
		$failedLineItemsToCreatives = array();

        foreach ($lineItems as $i => $singleLineItem) {
			$skipLineItemId = ( fn() => $this->id )->call( $singleLineItem );
			if ( $skipLineItemId === 6799123339 ) {
				printf("Found line item %s, skipping...\n", $skipLineItemId);
				continue;
			}

			for ($creativeLoopIndex = 0; $creativeLoopIndex < $multipleCreativesPerLineItem; $creativeLoopIndex++) {
				try {
					if ( $overrideCreativeSize ) {
						$creativeForm[ 'sizes' ] = $overrideCreativeSize;
					} else {
						$creativeForm[ 'sizes' ] = $this->getFirstCreativeSizeInString( $singleLineItem );
					}
					printf( "Creative sizes for lineItem id %s: %s\n", $lineItemId, $creativeForm[ 'sizes' ] );

					$creativeForm[ 'creativeName' ] = $this->buildCreativeName( $singleLineItem, $creativeNameSuffix, $creativeLoopIndex, $overrideCreativeSize, $appendLoopIndex, $offsetCreativeNameLoopIndex);
					printf( "The creative name will be: %s\n", $creativeForm[ 'creativeName' ] );

//                	return;
//					continue;

					if (/*$i === 0 || */ $forceNewCreative === true ) {
//                	printf("forceNewCreative was true\n");
                    	$creativeId = $creativeService->createFromTemplate( $creativeForm );
						// FOR DEBUGGING ONLY
//						$creativeId = $testCreativeIds[$creativeLoopIndex];
						$createdCreatives[] = $creativeId;
					}

//                return;

					if ( intval( $creativeId ) > 0 ) {
						$lineItemId = $singleLineItem->getId();
						$response = $lineItemCreativeAssociationService->create( $creativeId, $lineItemId, $overrideCreativeSize );

						if ( $response[ 'success' ] === true ) {
							$lineItemsWithNewCreatives[] = $lineItemId;
						} else {
							$errorMsgs[] = $response[ 'message' ];
							$failedLineItemsToCreatives[$singleLineItem->getId()][] = $creativeId;
						}
					} else {
						$errorMsgs[] = 'Could not create a creative';
					}
				} catch ( \Exception $e ) {
					$failedLineItemsToCreatives[$singleLineItem->getId()][] = $creativeId;
					$errorMsgs[] = $e->getMessage();
				}

			}
        }

        printf( "\nCreated %d creative(s)\n", count($createdCreatives) );
        printf( "Updated %d line item(s)\n", count(array_unique($lineItemsWithNewCreatives)));

        if ( count($failedLineItemsToCreatives) > 0 ) {
            printf( "\nFailed for %d line item(s)\n", count($failedLineItemsToCreatives) );
            print_r( $failedLineItemsToCreatives );
            print_r($errorMsgs);
        }
    }

    private function getFirstCreativeSize($lineItem) {
        $creativePlaceholder = $lineItem->getCreativePlaceholders();

        if (!is_array($creativePlaceholder) || empty($creativePlaceholder)) {
            throw new \Exception('Line item (' . $lineItem->getId() . ') does not have creative placeholder' );
        }

        return $creativePlaceholder[0]->getSize();
    }

    private function getFirstCreativeSizeInString($lineItem) {
        $firstCreativeSize = $this->getFirstCreativeSize($lineItem);

        return $firstCreativeSize->getWidth() . 'x' . $firstCreativeSize->getHeight();
    }

    private function buildCreativeName($lineItem, $suffix = '', $creativeLoopIndex = 0, $overrideCreativeSize = null, $appendIndex = false, $offsetLoopIndex = 0): string {
        $pricePattern = "/\s{1}\d{1,}\.\d{2}/";
        $name = preg_replace($pricePattern, '', $lineItem->getName());
        $sizeString = $overrideCreativeSize ? $overrideCreativeSize : $this->getFirstCreativeSizeInString($lineItem);
        $name .=  ' - ' . $sizeString;

        if (!empty($suffix)) {
            $name .= ' ' . $suffix;
        }

        /* Append a numeric index value to the creative's name,
         * and wrap it in parentheses. */
        if ($appendIndex) {
			// Since loop indices are zero-based,
			// we add 1 to the index to make it 1-based.
        	$creativeLoopIndex += 1;
        	if ($offsetLoopIndex > 0) {
				$creativeLoopIndex += $offsetLoopIndex;
			}
			$name .= ' ' . '(' . $creativeLoopIndex . ')';
		}

        return $name;
    }

    private function parseCreativeVariables($input) {
        $explodedCreativeVariables = explode(';', $input );
        $creativeVariables = [];

        foreach( $explodedCreativeVariables as $item ) {
            $pair = explode( ':', $item);
            $creativeVariables[$pair[0]] = $pair[1];
        }

        return $creativeVariables;
    }
}
