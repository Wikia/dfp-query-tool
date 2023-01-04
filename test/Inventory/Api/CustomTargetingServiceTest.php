<?php
namespace Inventory\Api;

use Google\AdsApi\AdManager\v202208\CustomTargetingValue;
use Google\AdsApi\AdManager\v202208\CustomTargetingValuePage;
use PHPUnit\Framework\TestCase;

class CustomTargetingServiceTest extends TestCase {
    public function testGetKeyIds_emptyArray() {
        $pageMock = $this->createCustomTargetingValuePageReturningMock();
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenPageMock($pageMock);
        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            [],
            $customTargetingService->getKeyIds([]),
            "Failed empty arrays case"
        );
    }

    public function testGetKeyIds_existingKey() {
        $valueMock = $this->createCustomTargetingValueMock();
        $valueMock->method('getId')
            ->willReturn(666);

        $pageMock = $this->createCustomTargetingValuePageReturningMock([$valueMock]);
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenPageMock($pageMock);
        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            ['test-key-val-name' => 666],
            $customTargetingService->getKeyIds(['test-key-val-name']),
            "Failed mocked data with one result case"
        );
    }

    public function testGetKeyIds_existingKeys() {
        $valueMock = $this->createCustomTargetingValueMock();
        $valueMock->method('getId')
            ->willReturn(666);

        $pageMock = $this->createCustomTargetingValuePageReturningMock([$valueMock]);
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenPageMock($pageMock);
        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            ['test-key-val-name' => 666, 'test-key-val-name2' => 666],
            $customTargetingService->getKeyIds(['test-key-val-name', 'test-key-val-name2']),
            "Failed mocked data with one result case"
        );
    }

    public function testGetKeyIds_notExistingKey() {
        $pageMock = $this->createCustomTargetingValuePageReturningMock();
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenPageMock($pageMock);

        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);
        $this->expectException(CustomTargetingException::class);
        $customTargetingService->getKeyIds(['test-not-existing-key-val-name']);
    }

    public function testGetKeyIds_existingAndNotExistingKey() {
        $valueMock = $this->createCustomTargetingValueMock();
        $valueMock->method('getId')
            ->willReturn(666);

        $pageMock = $this->createCustomTargetingValuePageMock();
        $pageMock->method('getResults')
            ->willReturnOnConsecutiveCalls([$valueMock], []);

        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenPageMock($pageMock);

        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);
        $this->expectException(CustomTargetingException::class);
        $customTargetingService->getKeyIds((['test-key-val-name', 'test-not-existing-key-val-name']));
    }

    private function createCustomTargetingServiceMock() {
        return $this->createStub(\Google\AdsApi\AdManager\v202208\CustomTargetingService::class);
    }

    private function createCustomTargetingValueMock() {
        return $this->createStub(CustomTargetingValue::class);
    }

    private function createCustomTargetingValuePageMock() {
        return $this->createStub(CustomTargetingValuePage::class);
    }

    private function createCustomTargetingValuePageReturningMock($resultsMock = []) {
        $pageMock = $this->createCustomTargetingValuePageMock();
        $pageMock->method('getResults')
            ->willReturn($resultsMock);

        return $pageMock;
    }

    private function createCustomTargetingServiceReturningGivenPageMock($pageMock) {
        $customTargetingServiceMock = $this->createCustomTargetingServiceMock();
        $customTargetingServiceMock->method('getCustomTargetingKeysByStatement')
            ->willReturn($pageMock);

        return $customTargetingServiceMock;
    }

    private function makeExpectedResultForGetKeyIdsWithNotExistingKeys($expectedIds = [], $expectedNotExistingNames = []) {
        return ['ids' => $expectedIds, 'notExistingNames' => $expectedNotExistingNames];
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

    public function testRemoveValueFromKeyById_noResults() {
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenUpdateResult(null);
        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            false,
            $customTargetingService->removeValueFromKeyById(123, 456),
            "Failed no results case"
        );
    }

    public function testRemoveValueFromKeyById_someResults() {
        $updateResultMock = $this->createUpdateResultsMock();
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenUpdateResult($updateResultMock);
        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            true,
            $customTargetingService->removeValueFromKeyById(123, 456),
            "Failed some results case"
        );
    }

    private function createCustomTargetingServiceReturningGivenUpdateResult($updateResultMock) {
        $customTargetingServiceMock = $this->createCustomTargetingServiceMock();
        $customTargetingServiceMock->method('performCustomTargetingValueAction')
            ->willReturn($updateResultMock);

        return $customTargetingServiceMock;
    }

    private function createUpdateResultsMock() {
        $updateResultMock = $this->createStub(\Google\AdsApi\AdManager\v202205\UpdateResult::class);
        $updateResultMock->method('getNumChanges')
            ->willReturn(7);

        return $updateResultMock;
    }
}
