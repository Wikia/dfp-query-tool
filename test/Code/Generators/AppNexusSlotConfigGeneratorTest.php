<?php
namespace Code\Command;

use PHPUnit\Framework\TestCase;

class AppNexusSlotConfigGeneratorTest extends TestCase {
    public function testUpdateAfterRowIteration_newSlot() {
        $generator = new AppNexusSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123 ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90] ],
					'placementId' => [123]
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and one size"
        );
    }

    public function testUpdateAfterRowIteration_existingSlot() {
        $generator = new AppNexusSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123 ]);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 124, [ 'top_leaderboard', 970, 250, 124 ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
					'placementId' => [123, 124]
				]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and two sizes"
        );
    }

    public function testUpdateAfterRowIteration_twoSlots() {
        $generator = new AppNexusSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123 ]);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 124, [ 'top_leaderboard', 970, 250, 123 ]);
        $generator->updateAfterRowIteration('top_boxad', [300, 250], 125, [ 'top_boxad', 300, 250, 123 ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
					'placementId' => [123, 124]
                ],
                'top_boxad' => [
                    'sizes' => [ [300, 250] ],
					'placementId' => [125]
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for two slots"
        );
    }

}
