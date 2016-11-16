<?php

namespace Inventory\Api;

use Common\Api\Authenticator;

class LineItemCreativeAssociationService {
	private $customTargetingService;

	public function __construct() {
		$this->customTargetingService = new CustomTargetingService();
	}

	public function create( $creativeId, $lineItemId ) {
		$success = true;
		$message = '';

		try {
			$user = Authenticator::getUser();
			$lineItemCreativeAssociationService = $user->GetService( 'LineItemCreativeAssociationService', 'v201608' );

			$lineItemCreativeAssociation = new \LineItemCreativeAssociation();
			$lineItemCreativeAssociation->creativeId = $creativeId;
			$lineItemCreativeAssociation->lineItemId = $lineItemId;
			$licas = [$lineItemCreativeAssociation];

			$licas = $lineItemCreativeAssociationService->createLineItemCreativeAssociations($licas);

			if (!isset($licas)) {
				$success = false;
				$message = 'licas not created';
			}

		} catch ( \Exception $e ) {
			$success = false;
			$message = $e->getMessage();
		}

		return [
			'status' => $success ? 'success' : 'error',
			'message' => $message,
			'creativeId' => $creativeId
		];
	}
}
