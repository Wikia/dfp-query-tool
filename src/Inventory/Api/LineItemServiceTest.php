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
        $networkServiceMock = $this->createStub(\Google\AdsApi\AdManager\v202105\NetworkService::class);
        $networkServiceMock->method('getCurrentNetwork')
            ->willReturn($this->createStub(\Google\AdsApi\AdManager\v202105\Network::class));
        
        return $networkServiceMock;
    }

    private function createLineItemPageStub() {
        return $this->createStub(\Google\AdsApi\AdManager\v202105\LineItemPage::class);
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
        $lineItemServiceMock = $this->createStub(\Google\AdsApi\AdManager\v202105\LineItemService::class);
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

    private function createLineItemMock($getCustomTargetingMock = null) {
        $targetingMock = $this->createStub(\Google\AdsApi\AdManager\v202105\Targeting::class);
        $targetingMock->method('getCustomTargeting')
            ->willReturn($getCustomTargetingMock);

        $lineItemMock = $this->createLineItemStub();
        $lineItemMock->method('getTargeting')
            ->willReturn($targetingMock);

        $lineItemMock->method('getId')
            ->willReturn(1234567890);

        $lineItemMock->method('getOrderId')
            ->willReturn(9876543210);

        return $lineItemMock;
    }

    private function createLineItemStub() {
        return $this->createStub(\Google\AdsApi\AdManager\v202105\LineItem::class);
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

    private function createCustomCriteriaSetMock() {
        $customCriteriaMock = $this->createStub(\Google\AdsApi\AdManager\v202105\CustomCriteria::class);
        $customCriteriaMock->method('getKeyId')
            ->willReturn(666);

        $customCriteriaNodeMock = $this->createStub(\Google\AdsApi\AdManager\v202105\CustomCriteriaSet::class);
        $customCriteriaNodeMock->method('getChildren')
            ->willReturn([$customCriteriaMock]);

        $customCriteriaSetMock = $this->createStub(\Google\AdsApi\AdManager\v202105\CustomCriteriaSet::class);
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
}
