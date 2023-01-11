<?php

namespace Code\Command;

class PubmaticSlotConfigGenerator implements PrebidSlotConfigGenerator {
    protected array $slotConfigArray;

    public function __constructor() {
        $this->slotConfigArray = [];
    }

    public function updateAfterRowIteration($slotName, $slotSizes, $slotBidderId, $row) {
        if ( empty($this->slotConfigArray[$slotName]) ) {
            $this->slotConfigArray[$slotName] = [
                'sizes' => [$slotSizes],
                'ids' => [$slotBidderId],
            ];
        } else {
            $this->slotConfigArray[$slotName]['sizes'][] = $slotSizes;
            $this->slotConfigArray[$slotName]['ids'][] = $slotBidderId;
        }
    }

    public function updateAfterLoop() {}

    public function getSlotConfigArray(): array {
        return $this->slotConfigArray;
    }

    public function getBidderConfig(): array {
        return [
            'slots' => $this->getSlotConfigArray(),
        ];
    }
}
