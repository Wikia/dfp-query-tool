<?php
namespace Inventory\Api;

use PHPUnit\Framework\TestCase;

class CustomTargetingServiceTest extends TestCase {
    public function testGetKeyIds() {
        $customTargetingService = new CustomTargetingService();

        $this->assertSame(
            [],
            $customTargetingService->getKeyIds([]),
            "Failed empty arrays case"
        );

        $valueMock = $this->createMock(\Google\AdsApi\AdManager\v202105\CustomTargetingValue::class);
        $valueMock->method('getId')
            ->willReturn(666);

        $pageMock = $this->createMock(\Google\AdsApi\AdManager\v202105\CustomTargetingValuePage::class);
        $pageMock->method('getResults')
            ->willReturn([$valueMock]);

        $customTargetingServiceMock = $this->createStub(\Google\AdsApi\AdManager\v202105\CustomTargetingService::class);
        $customTargetingServiceMock->method('getCustomTargetingKeysByStatement')
            ->willReturn($pageMock);

        $this->assertSame(
            [666],
            $customTargetingService->getKeyIds(['src']),
            "Failed empty arrays case"
        );
    }

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
