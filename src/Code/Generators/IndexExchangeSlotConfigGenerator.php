<?php

namespace Code\Command;

class IndexExchangeSlotConfigGenerator implements PrebidSlotConfigGenerator {
    protected array $slotConfigArray;

    public function __constructor() {
        $this->slotConfigArray = [];
    }

    public function updateAfterRowIteration($slotName, $slotSizes, $slotBidderId, $row) {
        if ( empty($this->slotConfigArray[$slotName]) ) {
            $this->slotConfigArray[$slotName] = [
                'sizes' => [$slotSizes],
                'siteId' => [$slotBidderId],
            ];
        } else {
            $this->slotConfigArray[$slotName]['sizes'][] = $slotSizes;
            $this->slotConfigArray[$slotName]['siteId'][] = $slotBidderId;
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
