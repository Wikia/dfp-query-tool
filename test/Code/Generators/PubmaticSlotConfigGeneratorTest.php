<?php
namespace Code\Command;

use PHPUnit\Framework\TestCase;

class PubmaticSlotConfigGeneratorTest extends TestCase {
    public function testUpdateAfterRowIteration_newSlot() {
        $generator = new PubmaticSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, []);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90] ],
                    'ids' => [ 123 ],
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and one size"
        );
    }

    public function testUpdateAfterRowIteration_existingSlot() {
        $generator = new PubmaticSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, []);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 456, []);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
                    'ids' => [ 123, 456 ],
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and two sizes"
        );
    }

    public function testUpdateAfterRowIteration_twoSlot() {
        $generator = new PubmaticSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, []);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 456, []);
        $generator->updateAfterRowIteration('top_boxad', [300, 250], 789, []);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
                    'ids' => [ 123, 456 ],
                ],
                'top_boxad' => [
                    'sizes' => [ [300, 250] ],
                    'ids' => [ 789 ],
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and two sizes"
        );
    }
}
