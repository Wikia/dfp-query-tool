<?php

namespace Code\Command;

class MedianetSlotConfigGenerator implements PrebidSlotConfigGenerator {
    protected array $slotConfigArray;

    public function __constructor() {
        $this->slotConfigArray = [];
    }

    public function updateAfterRowIteration($slotName, $slotSizes, $slotBidderId, $row) {
        $cid = $row[4];

        if ( empty($this->slotConfigArray[$slotName]) ) {
            $this->slotConfigArray[$slotName] = [
                'sizes' => [$slotSizes],
                'crid' => [$slotBidderId],
                'cid' => $cid,
            ];
        } else {
            $this->slotConfigArray[$slotName]['sizes'][] = $slotSizes;
            $this->slotConfigArray[$slotName]['crid'][] = $slotBidderId;
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
