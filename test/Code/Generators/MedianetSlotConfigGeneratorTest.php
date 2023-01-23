<?php
namespace Code\Command;

use PHPUnit\Framework\TestCase;

class MedianetSlotConfigGeneratorTest extends TestCase {
    public function testUpdateAfterRowIteration_newSlot() {
        $generator = new MedianetSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123, '123XYZ' ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90] ],
                    'crid' => [123],
                    'cid' => '123XYZ',
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and one size"
        );
    }

    public function testUpdateAfterRowIteration_existingSlot() {
        $generator = new MedianetSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123, '123XYZ' ]);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 456, [ 'top_leaderboard', 970, 250, 456, '123XYZ' ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
                    'crid' => [123, 456],
                    'cid' => '123XYZ',
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and two sizes"
        );
    }

    public function testUpdateAfterRowIteration_twoSlots() {
        $generator = new MedianetSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, [ 'top_leaderboard', 728, 90, 123, '123XYZ' ]);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 456, [ 'top_leaderboard', 970, 250, 456, '123XYZ' ]);
        $generator->updateAfterRowIteration('top_boxad', [300, 250], 234, [ 'top_boxad', 300, 250, 234, '123XYZ' ]);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
                    'crid' => [123, 456],
                    'cid' => '123XYZ',
                ],
                'top_boxad' => [
                    'sizes' => [ [300, 250] ],
                    'crid' => [234],
                    'cid' => '123XYZ',
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for two slots"
        );
    }
}
