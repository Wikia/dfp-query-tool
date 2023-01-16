<?php

namespace Code\Command;

class MagniteSlotConfigGenerator implements PrebidSlotConfigGenerator {
    protected array $slotConfigArray;

    public function __constructor() {
        $this->slotConfigArray = [];
    }

    public function updateAfterRowIteration($slotName, $slotSizes, $slotBidderId, $row) {
        $placementId = $row[4];

        if ( empty($this->slotConfigArray[$slotName]) ) {
            $this->slotConfigArray[$slotName] = [
                'sizes' => [$slotSizes],
                'siteId' => $slotBidderId,
                'zoneId' => $placementId,
            ];
        } else {
            $this->slotConfigArray[$slotName]['sizes'][] = $slotSizes;
        }
    }

    public function updateAfterLoop() {
    }

    public function getSlotConfigArray(): array {
        return $this->slotConfigArray;
    }

    public function getBidderConfig(): array {
        return [
            'slots' => $this->getSlotConfigArray()
        ];
    }
}
