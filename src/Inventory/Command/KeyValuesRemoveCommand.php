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
    private $skipLineItemCheck = false;

    private $keyNameFromInput;
    private $keyId;

    private $valuesFromInput;
    private $valuesIdsToRemove = [];

    private $keyValsFoundUsed = [];

    private $customTargetingService;
    private $lineItemService;

    static $FOREGROUND_COLOR = [
        'black' => 30,
        'green' => 32,
        'white' => 37,
    ];

    static $BACKGROUND_COLOR = [
        'black' => 40,
        'red' => 41,
        'yellow' => 43,
    ];

    public function __construct($app, $name = null) {
        parent::__construct($name);

        $this->customTargetingService = new CustomTargetingService();
        $this->lineItemService = new LineItemService();
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
            )
            ->addOption(
                'skip-line-item-check',
                null,
                InputOption::VALUE_OPTIONAL,
                'Run without scanning if the key-values are being used in any line-item custom targeting',
                'no'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $this->keyNameFromInput = $input->getArgument('key');
        $this->valuesFromInput = explode(',', $input->getArgument('values'));
        $this->dryRun = $input->getOption( 'dry-run' ) === 'no' ? false : true;
        $this->skipLineItemCheck = $input->getOption( 'skip-line-item-check' ) === 'no' ? false : true;

        try {
            $this->displayDryRunOrRealRunWarning();
            $this->checkKeyId();
            $this->checkValuesIds();
            $this->displayMessageAboutScanningForLineItems();
            $this->checkLineItemsForKeyValsToRemove();
            $this->removeValuesIfDryModeIsDisabled();

            echo PHP_EOL . 'Done.' . PHP_EOL;
        } catch (CustomTargetingException $e) {
            echo 'Unexpected exception: ' . $e->getMessage() . PHP_EOL;
        }
    }

    private function displayDryRunOrRealRunWarning() {
        if ($this->dryRun) {
            echo $this->info(' DRY RUN ENABLED ') . PHP_EOL . PHP_EOL;
        } else {
            echo $this->warning(' THIS IS NOT A DRY RUN !!! ') . PHP_EOL . PHP_EOL;
        }
    }

    private function printInColors($message, $foregroundColor, $backgroundColor) {
        return sprintf(
            "\e[0;%s;%sm%s\e[0m",
            $foregroundColor === null ? static::$FOREGROUND_COLOR['green'] : $foregroundColor,
            $backgroundColor === null ? static::$BACKGROUND_COLOR['black'] : $backgroundColor,
            $message
        );
    }

    private function info($message) {
        return $this->printInColors($message, static::$FOREGROUND_COLOR['black'], static::$BACKGROUND_COLOR['yellow']);
    }

    private function warning($message) {
        return $this->printInColors($message, static::$FOREGROUND_COLOR['white'], static::$BACKGROUND_COLOR['red']);
    }

    private function checkKeyId() {
        try {
            $keysIds = $this->customTargetingService->getKeyIds([$this->keyNameFromInput]);
            $this->keyId = array_shift($keysIds);
        } catch (CustomTargetingException $e) {
            throw $e;
        }
    }

    private function checkValuesIds() {
        try {
            $this->valuesIdsToRemove = $this->customTargetingService->getValueIds($this->keyId, $this->valuesFromInput);

            if ( empty($this->valuesIdsToRemove) ) {
                throw new \Exception('Nothing to remove.');
            }
        } catch (CustomTargetingException $e) {
            throw $e;
        }
    }

    private function displayMessageAboutScanningForLineItems() {
        if ($this->skipLineItemCheck) {
            echo $this->warning(' Skipping check for line-items using the key-vals! ') . PHP_EOL . PHP_EOL;
        } else {
            echo 'Looking for line-items using the key-val values...' . PHP_EOL;
        }
    }

    private function checkLineItemsForKeyValsToRemove() {
        if (!$this->skipLineItemCheck) {
            $lineItemsFound = $this->lineItemService->findLineItemIdsByKeyValues($this->keyId, $this->valuesIdsToRemove);
            foreach($lineItemsFound as $lineItem) {
                $this->keyValsFoundUsed[$lineItem['found_value_id']] = $lineItem['line_item_id'];
            }
        }
    }

    private function removeValuesIfDryModeIsDisabled() {
        if ($this->dryRun) {
            echo PHP_EOL . 'Dry-run summary:' . PHP_EOL;
        } else {
            echo 'Removing key-val values...' . PHP_EOL;
        }

        foreach( $this->valuesIdsToRemove as $valueId ) {
            if (in_array($valueId, array_keys($this->keyValsFoundUsed))) {
                $this->displayValueNotRemovedMessage($valueId);
            } else {
                $this->removeValueIfDryModeIsDisabled($valueId);
            }
        }
    }

    private function removeValueIfDryModeIsDisabled($valueId) {
        if ($this->dryRun) {
            $this->displayRemovedValueMessage($valueId);
            return;
        }

        if ($this->customTargetingService->removeValueFromKeyById($this->keyId, $valueId)) {
            $this->displayRemovedValueMessage($valueId);
        } else {
            $this->displayCouldNotRemoveValueMessage($valueId);
        }
    }

    private function displayValueNotRemovedMessage($valueId) {
        echo $this->info(
            sprintf(
                ' ! Value %d (%s) of key %d (%s) has not been deleted because it used in line-item ID %d ' . PHP_EOL,
                $valueId,
                $this->getValueIdName($valueId),
                $this->keyId,
                $this->keyNameFromInput,
                $this->keyValsFoundUsed[$valueId]
            )
        );
    }

    private function displayRemovedValueMessage($valueId) {
        echo sprintf(
            ' ✔ Value %d (%s) of key %d (%s) has been removed' . PHP_EOL,
            $valueId,
            $this->getValueIdName($valueId),
            $this->keyId,
            $this->keyNameFromInput
        );
    }

    private function displayCouldNotRemoveValueMessage($valueId) {
        echo $this->info(
            sprintf(
                ' ⅹ Value %d (%s) of key %d (%s) has not been deleted because of an error - try again ' . PHP_EOL,
                $valueId,
                $this->getValueIdName($valueId),
                $this->keyId,
                $this->keyNameFromInput
            )
        );
    }

    private function getValueIdName($valueId) {
        return empty($this->valuesFromInput[array_search($valueId, $this->valuesIdsToRemove)])
            ? 'undefined'
            : $this->valuesFromInput[array_search($valueId, $this->valuesIdsToRemove)];
    }
}
