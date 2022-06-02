<?php

namespace Inventory\Command;

use Inventory\Api\CustomTargetingService;
use Inventory\Api\CustomTargetingException;
use Inventory\Api\LineItemService;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class KeyValuesRemoveCommand extends Command
{
    private $dryRun = true;

    private $keyNameFromInput;
    private $keyId;

    private $valuesFromInput;
    private $valuesIdsToRemove;

    private $keyValsFoundUsed;

    public function __construct($app, $name = null) {
        parent::__construct($name);
    }

    protected function configure() {
        $this->setName('key-values:remove-values')
            ->setDescription('Remove GAM key-val values')
            ->addArgument('key', InputArgument::REQUIRED, 'Name of the key-val')
            ->addArgument('values', InputArgument::REQUIRED, 'Values of key-val to remove (separated with coma)')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_OPTIONAL,
                'Run without actually removing anything; in order to force the removal go with --dry-run=no',
                'yes'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->keyNameFromInput = $input->getArgument('key');
        $this->valuesFromInput = explode(',', $input->getArgument('values'));
        $this->dryRun = $input->getOption( 'dry-run' ) === 'no' ? false : true;

        try {
            $this->displayDryRunOrRealRunWarning();
            $this->checkKeyId();
            $this->checkValuesIds();
            $this->displayMessageAboutScanningForLineItems();
            $this->checkLineItemsForKeyValsToRemove();
            $this->deleteKeyValsIfDryModeIsDisabled();
        } catch (CustomTargetingException $e) {
            echo 'Unexpected exception: ' . $e->getMessage() . PHP_EOL;
        }
    }

    private function displayDryRunOrRealRunWarning() {
        if ($this->dryRun) {
            echo $this->printInColors(' DRY RUN ENABLED ', '1;30', '43') . PHP_EOL . PHP_EOL;
        } else {
            echo $this->printInColors('THIS IS NOT A DRY RUN !!!', '1;37', '41') . PHP_EOL . PHP_EOL;
        }
    }

    private function printInColors($message, $foregroundColor = '0;32', $backgroundColor = '40') {
        return sprintf("\e[%s;%sm%s\e[0m", $foregroundColor, $backgroundColor, $message);
    }

    private function checkKeyId() {
        $targetingService = new CustomTargetingService();

        try {
            $keysIds = $targetingService->getKeyIds([$this->keyNameFromInput]);
            $this->keyId = array_shift($keysIds);
        } catch (CustomTargetingException $e) {
            throw $e;
        }
    }

    private function checkValuesIds() {
        $targetingService = new CustomTargetingService();

        try {
            $this->valuesIdsToRemove = $targetingService->getValueIds($this->keyId, $this->valuesFromInput);
        } catch (CustomTargetingException $e) {
            throw $e;
        }
    }

    private function displayMessageAboutScanningForLineItems() {
        if ( !empty($this->valuesIdsToRemove) ) {
            echo 'Looking for line-items using the key-vals pair...' . PHP_EOL;
        } else {
            echo 'Nothing more to do. Done.' . PHP_EOL;
        }
    }

    private function checkLineItemsForKeyValsToRemove() {
        if ( !empty($this->valuesIdsToRemove) ) {
            $lineItemService = new LineItemService();
            $lineItemsFound = $lineItemService->findLineItemIdsByKeyValues($this->keyId, $this->valuesIdsToRemove);
            foreach($lineItemsFound as $lineItem) {
                $this->keyValsFoundUsed[$lineItem['found_value_id']] = $lineItem['line_item_id'];
            }
        }
    }

    private function deleteKeyValsIfDryModeIsDisabled() {
        if (!$this->dryRun && !empty($this->valuesIdsToRemove)) {
            echo 'Removing key-vals...' . PHP_EOL;
        }

        if ($this->dryRun && !empty($this->valuesIdsToRemove)) {
            echo PHP_EOL . 'Dry-run summary:' . PHP_EOL;
            foreach( $this->valuesIdsToRemove as $valueId ) {
                if (!empty($this->keyValsFoundUsed)) {
                    if (in_array($valueId, array_keys($this->keyValsFoundUsed))) {
                        $this->displayValueNotRemovedMessage($valueId);
                    } else {
                        $this->displayRemovedValueMessage($valueId);
                    }
                } else {
                    $this->displayRemovedValueMessage($valueId);
                }
            }
        }
    }

    private function displayRemovedValueMessage($valueId) {
        printf(
            ' ✔ Value %d (%s) of key %d (%s) has been removed' . PHP_EOL,
            $valueId,
            $this->getValueIdName($valueId),
            $this->keyId,
            $this->keyNameFromInput
        );
    }

    private function displayValueNotRemovedMessage($valueId) {
        echo $this->printInColors(
            sprintf(
                ' ⅹ Value %d (%s) of key %d (%s) has not been deleted because it used in line-item ID %d ' . PHP_EOL,
                $valueId,
                $this->getValueIdName($valueId),
                $this->keyId,
                $this->keyNameFromInput,
                $this->keyValsFoundUsed[$valueId]
            ),
            '0;30',
            '43'
        );
    }

    private function getValueIdName($valueId) {
        return empty($this->valuesFromInput[array_search($valueId, $this->valuesIdsToRemove)])
            ? 'undefined'
            : $this->valuesFromInput[array_search($valueId, $this->valuesIdsToRemove)];
    }
}
