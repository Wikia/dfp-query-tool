<?php

namespace Creative\Command;


use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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

        printf("Order ID: %d\n", $orderId);
        printf("Creative template ID: %d\n", $creativeTemplateId);
    }
}