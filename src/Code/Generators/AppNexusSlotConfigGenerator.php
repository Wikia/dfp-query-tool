<?php

namespace Code\Command;

class AppNexusSlotConfigGenerator implements PrebidSlotConfigGenerator {
    protected array $slotConfigArray;
    protected array $placements;

    public function __constructor() {
        $this->slotConfigArray = [];
    }

    public function updateAfterRowIteration($slotName, $slotSizes, $slotBidderId, $row) {
        $placement = $row[4];

        if ( empty($this->slotConfigArray[$slotName]) ) {
            $this->slotConfigArray[$slotName] = [
                'sizes' => [$slotSizes],
                'position' => $placement,
            ];
        } else {
            $this->slotConfigArray[$slotName]['sizes'][] = $slotSizes;
        }

        if ( empty($this->placements[$placement]) ) {
            $this->placements[$placement] = $slotBidderId;
        }
    }

    public function updateAfterLoop() {
        $this->slotConfigArray['placements'] = $this->placements;
    }

    public function getSlotConfigArray(): array {
        return $this->slotConfigArray;
    }

    public function getPlacementsConfig(): array {
        return $this->placements;
    }

    public function getBidderConfig(): array {
        return [
            'slots' => $this->getSlotConfigArray(),
            'placements' => $this->getPlacementsConfig(),
        ];
    }
}
