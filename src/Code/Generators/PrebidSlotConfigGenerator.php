<?php

namespace Code\Command;

interface PrebidSlotConfigGenerator {
    public function updateAfterRowIteration($slotName, $slotSizes, $slotBidderId, $row);
    public function updateAfterLoop();
    public function getBidderConfig(): array;
}
