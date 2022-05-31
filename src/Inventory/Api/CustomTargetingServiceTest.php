<?php
namespace Inventory\Api;

use PHPUnit\Framework\TestCase;

class CustomTargetingServiceTest extends TestCase {
    public function testGetKeyIds() {
        $valueMock = $this->createCustomTargetingValueMock();
        $valueMock->method('getId')
            ->willReturn(666);

        $pageMock = $this->createCustomTargetingValuePageMock();
        $pageMock->method('getResults')
            ->willReturn([$valueMock]);

        $customTargetingServiceMock = $this->createCustomTargetingServiceMock();
        $customTargetingServiceMock->method('getCustomTargetingKeysByStatement')
            ->willReturn($pageMock);

        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            [],
            $customTargetingService->getKeyIds([]),
            "Failed empty arrays case"
        );

        $this->assertSame(
            [666],
            $customTargetingService->getKeyIds(['test-key-val-name']),
            "Failed mocked data with one result case"
        );

        $pageMock = $this->createCustomTargetingValuePageMock();
        $pageMock->method('getResults')
            ->willReturn([]);

        $customTargetingServiceMock = $this->createCustomTargetingServiceMock();
        $customTargetingServiceMock->method('getCustomTargetingKeysByStatement')
            ->willReturn($pageMock);

        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);
        $this->expectException(CustomTargetingException::class);
        $customTargetingService->getKeyIds(['test-not-existing-key-val-name']);
    }

    private function createCustomTargetingServiceMock() {
        return $this->createStub(\Google\AdsApi\AdManager\v202105\CustomTargetingService::class);
    }

    private function createCustomTargetingValueMock() {
        return $this->createStub(\Google\AdsApi\AdManager\v202105\CustomTargetingValue::class);
    }

    private function createCustomTargetingValuePageMock() {
        return $this->createStub(\Google\AdsApi\AdManager\v202105\CustomTargetingValuePage::class);
    }

    public function testGetKeyIdsWithNotExistingKeys_forEmptyArray() {
        $pageMock = $this->createCustomTargetingValuePageReturningMock();
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenPageMock($pageMock);
        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            $this->makeExpectedResultForGetKeyIdsWithNotExistingKeys(),
            $customTargetingService->getKeyIdsWithNotExistingKeys([]),
            "Failed empty arrays case"
        );
    }

    public function testGetKeyIdsWithNotExistingKeys_forExistingKey() {
        $valueMock = $this->createCustomTargetingValueMock();
        $valueMock->method('getId')
            ->willReturn(666);

        $pageMock = $this->createCustomTargetingValuePageReturningMock([$valueMock]);
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenPageMock($pageMock);
        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            $this->makeExpectedResultForGetKeyIdsWithNotExistingKeys([666]),
            $customTargetingService->getKeyIdsWithNotExistingKeys(['test-key-val-name']),
            "Failed mocked data with one not existing case"
        );
    }

    public function testGetKeyIdsWithNotExistingKeys_forNonExistingKey() {
        $pageMock = $this->createCustomTargetingValuePageReturningMock();
        $customTargetingServiceMock = $this->createCustomTargetingServiceReturningGivenPageMock($pageMock);
        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            $this->makeExpectedResultForGetKeyIdsWithNotExistingKeys([], ['test-not-existing-key-val-name']),
            $customTargetingService->getKeyIdsWithNotExistingKeys(['test-not-existing-key-val-name']),
            "Failed mocked data with one result case and one not existing case"
        );
    }

    public function testGetKeyIdsWithNotExistingKeys_forExistingAndNonExistingKey() {
        $valueMock = $this->createCustomTargetingValueMock();
        $valueMock->method('getId')
            ->willReturn(666);

        $pageMock = $this->createCustomTargetingValuePageMock();
        $pageMock->method('getResults')
            ->willReturnOnConsecutiveCalls([$valueMock], []);

        $customTargetingServiceMock = $this->createCustomTargetingServiceMock();
        $customTargetingServiceMock->method('getCustomTargetingKeysByStatement')
            ->willReturn($pageMock);

        $customTargetingService = new CustomTargetingService($customTargetingServiceMock);

        $this->assertSame(
            $this->makeExpectedResultForGetKeyIdsWithNotExistingKeys([666], ['test-not-existing-key-val-name']),
            $customTargetingService->getKeyIdsWithNotExistingKeys(['test-key-val-name', 'test-not-existing-key-val-name']),
            "Failed mocked data with one result case and one not existing case"
        );
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
}
