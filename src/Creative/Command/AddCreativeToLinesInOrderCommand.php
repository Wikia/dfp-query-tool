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
                'creative-suffix',
                's',
                InputOption::VALUE_OPTIONAL,
                "Creative's name suffix"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $orderId = $input->getOption('order');
        $creativeTemplateId = $input->getOption('creative-template');
        $creativeNameSuffix = $input->getOption('creative-suffix');

        if ( is_null($orderId) || intval($orderId) === 0 ) {
            throw new InvalidOptionException('Invalid order ID');
        }

        if ( is_null($creativeTemplateId) || intval($creativeTemplateId) === 0 ) {
            throw new InvalidOptionException('Invalid creative template ID');
        }

        printf("Order ID: %d\n", $orderId);
        printf("Creative template ID: %d\n", $creativeTemplateId);

        $orderService = new OrderService();
        $lineItemService = new LineItemService();
        $creativeService = new CreativeService();
        $lineItemCreativeAssociationService = new LineItemCreativeAssociationService();

        $order = $orderService->getById($orderId);
        $creativeForm = [
            'advertiserId' => $order->getAdvertiserId(),
            'creativeTemplateId' => $creativeTemplateId
        ];

        $lineItems = $lineItemService->getLineItemsInOrder($orderId);

        $createdCratives = [];
        $lineItemsWithNewCreatives = [];
        $failedLineItems = [];
        $errorMsgs = [];

        printf("Adding creatives to %s line item(s)\n", count($lineItems));
        foreach ($lineItems as $i => $lineItem) {
            try {
                $creativeForm['sizes'] = $this->getFirstCreativeSizeInString($lineItem);
                $creativeForm['creativeName'] = $this->buildCreativeName($lineItem, $creativeNameSuffix);

                $creativeId = $creativeService->createFromTemplate( $creativeForm );
                if (intval($creativeId) > 0) {
                    $createdCratives[] = $creativeId;
                    $lineItemId = $lineItem->getId();
                    $response = $lineItemCreativeAssociationService->create($creativeId, $lineItemId);

                    if ($response['success'] === true) {
                        $lineItemsWithNewCreatives[] = $lineItemId;
                    } else {
                        $errorMsgs[] = $response['message'];
                    }
                } else {
                    $errorMsgs[] = 'Could not create a creative';
                }
            } catch (\Exception $e) {
                $failedLineItems[] = $lineItem->getId();
                $errorMsgs[] = $e->getMessage();
            }
        }

        printf( "\nCreated %d creative(s)\n", count($createdCratives) );
        printf( "Updated %d line item(s)\n", count($lineItemsWithNewCreatives) );

        if ( count($failedLineItems) > 0 ) {
            printf( "\nFailed for %d line item(s)\n", count($failedLineItems) );
            print_r( $failedLineItems );
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

    private function buildCreativeName($lineItem, $suffix = '') {
        $name = $lineItem->getName() . ' - ' . $this->getFirstCreativeSizeInString($lineItem);

        if (!empty($suffix)) {
            $name .= ' ' . $suffix;
        }

        return $name;
    }
}