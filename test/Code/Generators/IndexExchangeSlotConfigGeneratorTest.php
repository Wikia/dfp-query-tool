<?php
namespace Code\Command;

use PHPUnit\Framework\TestCase;

class IndexExchangeSlotConfigGeneratorTest extends TestCase {
    public function testUpdateAfterRowIteration_newSlot() {
        $generator = new IndexExchangeSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, []);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90] ],
                    'siteId' => [ 123 ],
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and one size"
        );
    }

    public function testUpdateAfterRowIteration_existingSlot() {
        $generator = new IndexExchangeSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, []);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 456, []);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
                    'siteId' => [ 123, 456 ],
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and two sizes"
        );
    }

    public function testUpdateAfterRowIteration_twoSlot() {
        $generator = new IndexExchangeSlotConfigGenerator();
        $generator->updateAfterRowIteration('top_leaderboard', [728, 90], 123, []);
        $generator->updateAfterRowIteration('top_leaderboard', [970, 250], 456, []);
        $generator->updateAfterRowIteration('top_boxad', [300, 250], 789, []);

        $this->assertSame(
            [
                'top_leaderboard' => [
                    'sizes' => [ [728, 90], [970, 250] ],
                    'siteId' => [ 123, 456 ],
                ],
                'top_boxad' => [
                    'sizes' => [ [300, 250] ],
                    'siteId' => [ 789 ],
                ]
            ],
            $generator->getSlotConfigArray(),
            "Failed updating slot configuration for one slot and two sizes"
        );
    }
}
