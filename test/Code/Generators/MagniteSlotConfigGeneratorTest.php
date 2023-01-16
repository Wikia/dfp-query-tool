<?php
namespace Code\Command;

use PHPUnit\Framework\TestCase;

class MagniteSlotConfigGeneratorTest extends TestCase {
    public function testUpdateAfterRowIteration_newSlot() {
        $generator = new MagniteSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123, 456 ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90] ],
                    'siteId' => 123,
                    'zoneId' => 456,
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and one size"
        );
    }
}
