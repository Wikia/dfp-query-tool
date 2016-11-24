<?php

namespace Inventory\Api;

use Common\Api\Authenticator;

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
			$user = Authenticator::getUser();

			if ( empty($lineItemId) ) {
				return $this->getIncorrectLineItemResult();
			} else {
				$lineItemCreativeAssociationService = $user->GetService( 'LineItemCreativeAssociationService', 'v201608' );
				$lineItemCreativeAssociations = [ ];

				foreach ( $processedCreativeIds as $extractedCreativeId ) {
					$lineItemCreativeAssociation = new \LineItemCreativeAssociation();
					$lineItemCreativeAssociation->creativeId = trim( $extractedCreativeId );
					$lineItemCreativeAssociation->lineItemId = $lineItemId;
					$lineItemCreativeAssociations[] = $lineItemCreativeAssociation;
				}

				$lica = $lineItemCreativeAssociationService->createLineItemCreativeAssociations( $lineItemCreativeAssociations );

				if ( !isset($lica) ) {
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
