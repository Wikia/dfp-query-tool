<?php
namespace Inventory\Api;

use PHPUnit\Framework\TestCase;

class CustomTargetingServiceTest extends TestCase {
    public function testGetValuesIdsFromMap() {
        $customTargetingService = new CustomTargetingService();

        $this->assertSame(
            [],
            $customTargetingService->getValuesIdsFromMap([], []),
            "Failed empty arrays case"
        );

        $this->assertSame(
            [1],
            $customTargetingService->getValuesIdsFromMap(['B'], ['A', 'B', 'C']),
            "Failed int keys case"
        );

        $this->assertSame(
            ['b'],
            $customTargetingService->getValuesIdsFromMap(['B'], ['a' => 'A', 'b' => 'B', 'c' => 'C']),
            "Failed string keys case"
        );

        $this->assertSame(
            [0, 'c'],
            $customTargetingService->getValuesIdsFromMap(['B', 'C'], ['B', 'a' => 'A', 'b' => 'B', 'c' => 'C']),
            "Failed search for more values (notice ignored 'b' for 'B')"
        );
    }
}
