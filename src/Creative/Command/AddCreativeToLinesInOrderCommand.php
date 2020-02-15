<?php

namespace Creative\Command;


use Inventory\Api\CreativeService;
use Inventory\Api\LineItemService;
use Inventory\Api\LineItemCreativeAssociationService;
use Knp\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        printf("========== Work in-progress ==========\n");

        $orderId = $input->getOption('order');
        $creativeTemplateId = $input->getOption('creative-template');

        if ( is_null($orderId) || intval($orderId) === 0 ) {
            throw new InvalidOptionException('Invalid order ID');
        }

        if ( is_null($creativeTemplateId) || intval($creativeTemplateId) === 0 ) {
            throw new InvalidOptionException('Invalid creative template ID');
        }

        printf("Order ID: %d\n", $orderId);
        printf("Creative template ID: %d\n", $creativeTemplateId);

        $lineItemService = new LineItemService();
        $creativeService = new CreativeService();
        $lineItemCreativeAssociationService = new LineItemCreativeAssociationService();

        $creativeForm = [
            'creativeName' => 'ztest MR 300x250 ' . time(),
            'sizes' => '300x250',
            'advertiserId' => '31918332',
            'creativeTemplateId' => $creativeTemplateId
        ];

        $lineItems = $lineItemService->getLineItemsInOrder($orderId);
        $count = count($lineItems);

        $createdCratives = [];
        $lineItemsWithNewCreatives = [];
        $failedLineItems = [];
        $errorMsgs = [];

        printf("Adding creatives to %s line item(s)\n", $count);
        foreach ($lineItems as $i => $lineItem) {
            try {
                $creativeId = $creativeService->createFromTemplate( $creativeForm );
                $createdCratives[] = $creativeId;

                $lineItemId = $lineItem->getId();
                $response = $lineItemCreativeAssociationService->create($creativeId, $lineItemId);

                if ($response['success'] === true) {
                    $lineItemsWithNewCreatives[] = $lineItemId;
                    echo ".";
                } else {
                    $errorMsgs[] = $response['message'];
                }
            } catch (\Exception $e) {
                $failedLineItems[] = $lineItem->getId();
                $errorMsgs[] = $e->getMessage();
                echo "!";
            }
        }

        printf( "\nCreated %d creative(s)\n", count($createdCratives) );
        printf( "Updated %d line item(s)\n", count($createdCratives) );

        if ( count($failedLineItems) > 0 ) {
            printf( "\nFailed for %d line item(s)\n", count($failedLineItems) );
            print_r( $failedLineItems );
        }
    }
}