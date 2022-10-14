<?php
namespace Inventory\Api;

use PHPUnit\Framework\TestCase;

class LineItemServiceTest extends TestCase {
    public function testFindLineItemIdsByKeys_forNotExistingKey() {
        $pageMock = $this->createLineItemPageMock();
        $lineItemServiceMock = $this->createLineItemServiceMock($pageMock);
        $lineItemService = new LineItemService($this->createNetworkServiceMock(), $lineItemServiceMock);

        $this->assertSame(
            [],
            $lineItemService->findLineItemIdsByKeys([123]),
            "Failed no results test case"
        );
    }

    private function createNetworkServiceMock() {
        $networkServiceMock = $this->createStub(\Google\AdsApi\AdManager\v202205\NetworkService::class);
        $networkServiceMock->method('getCurrentNetwork')
            ->willReturn($this->createStub(\Google\AdsApi\AdManager\v202205\Network::class));

        return $networkServiceMock;
    }

    private function createLineItemPageStub() {
        return $this->createStub(\Google\AdsApi\AdManager\v202205\LineItemPage::class);
    }

    private function createLineItemPageMock($getResultsMock = null, $getTotalResultSetSizeMock = 0) {
        $pageMock = $this->createLineItemPageStub();
        $pageMock->method('getResults')
            ->willReturn($getResultsMock);
        $pageMock->method('getTotalResultSetSize')
            ->willReturn($getTotalResultSetSizeMock);

        return $pageMock;
    }

    private function createLineItemServiceMock($pageMock) {
        $lineItemServiceMock = $this->createStub(\Google\AdsApi\AdManager\v202205\LineItemService::class);
        $lineItemServiceMock->method('getLineItemsByStatement')
            ->willReturn($pageMock);

        return $lineItemServiceMock;
    }

    public function testFindLineItemIdsByKeys_forOneLineItemWithEmptyTargeting() {
        $lineItemMock = $this->createLineItemMock();
        $pageMock = $this->createLineItemPageMock([$lineItemMock], 1);
        $lineItemServiceMock = $this->createLineItemServiceMock($pageMock);
        $lineItemService = new LineItemService($this->createNetworkServiceMock(), $lineItemServiceMock);

        $this->assertSame(
            [],
            $lineItemService->findLineItemIdsByKeys([123]),
            "Failed one line-item with empty custom targeting test case"
        );
    }

    private function createLineItemMock($getCustomTargetingMock = null, $getIdMock = 1234567890, $getOrderIdMock = 9876543210) {
        $targetingMock = $this->createStub(\Google\AdsApi\AdManager\v202205\Targeting::class);
        $targetingMock->method('getCustomTargeting')
            ->willReturn($getCustomTargetingMock);

        $lineItemMock = $this->createLineItemStub();
        $lineItemMock->method('getTargeting')
            ->willReturn($targetingMock);

        $lineItemMock->method('getId')
            ->willReturn($getIdMock);

        $lineItemMock->method('getOrderId')
            ->willReturn($getOrderIdMock);

        return $lineItemMock;
    }

    private function createLineItemStub() {
        return $this->createStub(\Google\AdsApi\AdManager\v202205\LineItem::class);
    }

    public function testFindLineItemIdsByKeys_forOneLineItemWithTargetingWithoutTheKeyVal() {
        $customTargetingMock = $this->createCustomCriteriaSetMock();
        $lineItemMock = $this->createLineItemMock($customTargetingMock);
        $pageMock = $this->createLineItemPageMock([$lineItemMock], 1);
        $lineItemServiceMock = $this->createLineItemServiceMock($pageMock);
        $lineItemService = new LineItemService($this->createNetworkServiceMock(), $lineItemServiceMock);

        $this->assertSame(
            [],
            $lineItemService->findLineItemIdsByKeys([123]),
            "Failed empty custom targeting test case"
        );
    }

    private function createCustomCriteriaSetMock($getKeyIdMock = 666, $getValueIdsMock = []) {
        $customCriteriaMock = $this->createStub(\Google\AdsApi\AdManager\v202205\CustomCriteria::class);
        $customCriteriaMock->method('getKeyId')
            ->willReturn($getKeyIdMock);
        $customCriteriaMock->method('getValueIds')
            ->willReturn($getValueIdsMock);

        $customCriteriaNodeMock = $this->createStub(\Google\AdsApi\AdManager\v202205\CustomCriteriaSet::class);
        $customCriteriaNodeMock->method('getChildren')
            ->willReturn([$customCriteriaMock]);

        $customCriteriaSetMock = $this->createStub(\Google\AdsApi\AdManager\v202205\CustomCriteriaSet::class);
        $customCriteriaSetMock->method('getChildren')
            ->willReturn([$customCriteriaNodeMock]);

        return $customCriteriaSetMock;
    }

    public function testFindLineItemIdsByKeys_forOneLineItemWithTargetingWithTheKeyVal() {
        $customTargetingMock = $this->createCustomCriteriaSetMock();
        $lineItemMock = $this->createLineItemMock($customTargetingMock);
        $pageMock = $this->createLineItemPageMock([$lineItemMock], 1);
        $lineItemServiceMock = $this->createLineItemServiceMock($pageMock);
        $lineItemService = new LineItemService($this->createNetworkServiceMock(), $lineItemServiceMock);

        $this->assertSame(
            [
                [
                    'line_item_id' => 1234567890,
                    'order_id' => 9876543210,
                ]
            ],
            $lineItemService->findLineItemIdsByKeys([666]),
            "Failed one custom targeting matching the key test case"
        );
    }

    public function testFindLineItemIdsByKeyValues_forNoMatchingLineItems() {
        $pageMock = $this->createLineItemPageMock();
        $lineItemServiceMock = $this->createLineItemServiceMock($pageMock);
        $lineItemService = new LineItemService($this->createNetworkServiceMock(), $lineItemServiceMock);

        $this->assertSame(
            [],
            $lineItemService->findLineItemIdsByKeyValues(123, [123, 456]),
            "Failed no matching line-items test case"
        );
    }

    public function testFindLineItemIdsByKeyValues_forLineItemsWithTargetingWithTheKeyVal() {
        $keyIdMock = 123;
        $valuesIdsMockForFirstLineItemMock = [231, 321, 456, 564, 654];
        $valuesIdsMockForSecondLineItemMock = [123, 321];
        $secondLineItemIdMock = 1122334455;
        $secondOrderIdMock = 6677889900;

        $pageMock = $this->createLineItemPageMock([
            $this->createLineItemMock($this->createCustomCriteriaSetMock()),
            $this->createLineItemMock(
                $this->createCustomCriteriaSetMock($keyIdMock, $valuesIdsMockForFirstLineItemMock)
            ),
            $this->createLineItemMock($this->createCustomCriteriaSetMock()),
            $this->createLineItemMock(
                $this->createCustomCriteriaSetMock($keyIdMock, $valuesIdsMockForSecondLineItemMock),
                $secondLineItemIdMock,
                $secondOrderIdMock
            ),
        ], 4);
        $lineItemServiceMock = $this->createLineItemServiceMock($pageMock);
        $lineItemService = new LineItemService($this->createNetworkServiceMock(), $lineItemServiceMock);

        $this->assertSame(
            [
                [
                    'line_item_id' => 1234567890,
                    'order_id' => 9876543210,
                    'found_value_id' => 456,
                ],
                [
                    'line_item_id' => $secondLineItemIdMock,
                    'order_id' => $secondOrderIdMock,
                    'found_value_id' => 123,
                ]
            ],
            $lineItemService->findLineItemIdsByKeyValues(123, [123, 456]),
            "Failed no matching line-items test case"
        );
    }
}
