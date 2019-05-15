<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\v201902\LineItemCreativeAssociation;

class LineItemCreativeAssociationService {
	private $customTargetingService;

	public function __construct() {
		$this->customTargetingService = new CustomTargetingService();
	}

	public function create( $creativeId, $lineItemId ) {

		if ( empty($creativeId) ) {
			return [
				'creativeSet' => false
			];
		}

		$response = [
			'success' => true,
			'creativeSet' => true,
			'creativeId' => $creativeId
		];

		$processedCreativeIds = $this->processCreativeId( $creativeId );
		try {
			if ( empty($lineItemId) ) {
				return $this->getIncorrectLineItemResult();
			} else {
				$lineItemCreativeAssociationService = AdManagerService::get(\Google\AdsApi\AdManager\v201902\LineItemCreativeAssociationService::class);
				$lineItemCreativeAssociations = [ ];

				foreach ( $processedCreativeIds as $extractedCreativeId ) {
					$lineItemCreativeAssociation = new LineItemCreativeAssociation();
					$lineItemCreativeAssociation->setCreativeId(trim( $extractedCreativeId ));
					$lineItemCreativeAssociation->setLineItemId($lineItemId);
					$lineItemCreativeAssociations[] = $lineItemCreativeAssociation;
				}

				$lica = null;
				for ($i = 0; $i < 10; $i++) {
					$lica = $lineItemCreativeAssociationService->createLineItemCreativeAssociations( $lineItemCreativeAssociations );

					if ($lica || isset($lica)) break;
					echo 'SOAP "createLineItemCreativeAssociations()" connection error - retrying (' . ($i + 1) . ")...\n";
				}

				if ( !$lica || !isset($lica) ) {
					$response['success'] = false;
					$response['message'] = 'line item - creative association not created';
				}
			}
		} catch ( \Exception $e ) {
			$response['success'] = false;
			$response['message'] = $e->getMessage();
		}

		return $response;
	}

	public function getIncorrectLineItemResult() {
		return [
			'success' => false,
			'message' => 'Line item creation failed - unable to associate creative',
			'creativeSet' => false
		];
	}

	private function processCreativeId( $creativeId ) {
		return explode( ',', $creativeId );
	}
}
