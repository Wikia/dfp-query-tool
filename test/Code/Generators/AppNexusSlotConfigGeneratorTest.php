<?php
namespace Code\Command;

use PHPUnit\Framework\TestCase;

class AppNexusSlotConfigGeneratorTest extends TestCase {
    public function testUpdateAfterRowIteration_newSlot() {
        $generator = new AppNexusSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123, 'atf' ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90] ],
                    'position' => 'atf',
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and one size"
        );
    }

    public function testUpdateAfterRowIteration_existingSlot() {
        $generator = new AppNexusSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123, 'atf' ]);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 123, [ 'top_leaderboard', 970, 250, 123, 'atf' ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
                    'position' => 'atf',
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and two sizes"
        );
    }

    public function testUpdateAfterRowIteration_twoSlot() {
        $generator = new AppNexusSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123, 'atf' ]);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 123, [ 'top_leaderboard', 970, 250, 123, 'atf' ]);
        $generator->updateAfterRowIteration('top_boxad', [300, 250], 123, [ 'top_boxad', 300, 250, 123, 'atf' ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
                    'position' => 'atf',
                ],
                'top_boxad' => [
                    'sizes' => [ [300, 250] ],
                    'position' => 'atf',
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for two slots"
        );
    }

    public function testUpdateAfterRowIteration_additionalDataAfterLoop() {
        $generator = new AppNexusSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123, 'atf' ]);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 123, [ 'top_leaderboard', 970, 250, 123, 'atf' ]);
        $generator->updateAfterRowIteration('top_boxad', [300, 250], 123, [ 'top_boxad', 300, 250, 123, 'atf' ]);
        $generator->updateAfterRowIteration('bottom_leaderboard', [728, 90], 456, [ 'bottom_leaderboard', 728, 90, 123, 'btf' ]);

        $this->assertSame(
            [
                'atf' => 123,
                'btf' => 456,
            ],
            $generator->getPlacementsConfig(),
            "Failed updating placements configuration with additional data"
        );
    }
}
