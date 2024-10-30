<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\Util\v202408\StatementBuilder;
use Google\AdsApi\AdManager\v202408\DeleteLineItemCreativeAssociations;
use Google\AdsApi\AdManager\v202408\LineItemCreativeAssociation;
use Google\AdsApi\AdManager\v202408\DeactivateLineItemCreativeAssociations;
use Google\AdsApi\AdManager\v202408\Size;


class LineItemCreativeAssociationService {
	private $customTargetingService;

	public function __construct() {
		$this->customTargetingService = new CustomTargetingService();
	}

	public function create( $creativeId, $lineItemId, $sizes ) {
		printf("CreativeId: %s | lineItemId: %s | sizes: %s \n", $creativeId, $lineItemId, $sizes);

		if ( empty($creativeId) ) {
			printf("CreativeId was not set.");
			return [
				'creativeSet' => false
			];
		}

		$response = [
			'success' => true,
			'creativeSet' => true,
			'creativeId' => $creativeId
		];

		if ($sizes) {
			$sizes = $this->getOverrideSizes($sizes);
		}

		$processedCreativeIds = $this->processCreativeId( $creativeId );
//		printf("Processed creativeIds are: %s\n", print_r($processedCreativeIds, true));

		try {
			if ( empty($lineItemId) ) {
//				printf("LineItemId was not set.");
				return $this->getIncorrectLineItemResult();
			} else {
//				printf("LineItemId was set.");
				$lineItemCreativeAssociationService = AdManagerService::get(\Google\AdsApi\AdManager\v202408\LineItemCreativeAssociationService::class);
				$lineItemCreativeAssociations = [ ];

				foreach ( $processedCreativeIds as $extractedCreativeId ) {
					$lineItemCreativeAssociation = new LineItemCreativeAssociation();
					$lineItemCreativeAssociation->setCreativeId(trim( $extractedCreativeId ));
					$lineItemCreativeAssociation->setLineItemId($lineItemId);

					if ($sizes) {
						$lineItemCreativeAssociation->setSizes($sizes);
					}

					$lineItemCreativeAssociations[] = $lineItemCreativeAssociation;
//					printf("lineItemCreativeAssociations[] is:\n%s\n", print_r($lineItemCreativeAssociations, true));
				}

				$lica = null;
				for ($i = 0; $i < 10; $i++) {
					$lica = $lineItemCreativeAssociationService->createLineItemCreativeAssociations( $lineItemCreativeAssociations );

					if ($lica || isset($lica)) {
//						printf("Network call was okay.");
						break;
					}
					printf("Something messed up in the network call.");
					echo 'SOAP "createLineItemCreativeAssociations()" connection error - retrying (' . ($i + 1) . ")...\n";
				}

				if ( !$lica || !isset($lica) ) {
					printf("Line item - creative association not created");
					$response['success'] = false;
					$response['message'] = 'line item - creative association not created';
				}
			}
		} catch ( \Exception $e ) {
			printf("Some exception occurred");
			$response['success'] = false;
			$response['message'] = $e->getMessage();
		}

//		printf("Printing creativeAssociation response before returning:\n%s\n", print_r($response, true));
		return $response;
	}

	private function getOverrideSizes($sizeList) {
		$sizesOverride = [];
		$sizes = explode(',', $sizeList);

		foreach ($sizes as $size) {
			list($width, $height) = explode('x', trim($size));
			$sizesOverride[] = new Size(intval($width), intval($height), false);
		}

//		printf("Overridden sizes: ", print_r($sizesOverride, true));
		return $sizesOverride;
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

	public function deactivate( $creativeId, $lineItemId ) {
		$response = [
			'success' => true,
		];

		$statementBuilder = (new StatementBuilder())
			->where('lineItemId = :lineItemId and creativeId = :creativeId')
			->withBindVariableValue('lineItemId', $lineItemId)
			->withBindVariableValue('creativeId', $creativeId);

		try {
			$lineItemCreativeAssociationService = AdManagerService::get(\Google\AdsApi\AdManager\v202408\LineItemCreativeAssociationService::class);
			$action = new DeactivateLineItemCreativeAssociations();

			$result = $lineItemCreativeAssociationService
				->performLineItemCreativeAssociationAction(
					$action,
					$statementBuilder->toStatement()
				);

			if ($result !== null && $result->getNumChanges() > 0) {
				$response['success'] = true;
			} else {
				$response['message'] = 'Could not deactivate creative in line item ' . $lineItemId;
			}
		} catch ( \Exception $e ) {
			$response['success'] = false;
			$response['message'] = $e->getMessage();
		}

		return $response;
	}

	public function remove( $lineItemId ) {
		$response = [
			'success' => true,
		];

		$statementBuilder = (new StatementBuilder())
			->where('lineItemId = :lineItemId')
			->withBindVariableValue('lineItemId', $lineItemId);

		try {
			$lineItemCreativeAssociationService = AdManagerService::get(\Google\AdsApi\AdManager\v202408\LineItemCreativeAssociationService::class);
			$action = new DeleteLineItemCreativeAssociations();

			$result = $lineItemCreativeAssociationService
				->performLineItemCreativeAssociationAction(
					$action,
					$statementBuilder->toStatement()
				);

			if ($result !== null && $result->getNumChanges() > 0) {
				$response['success'] = true;
			} else {
				$response['message'] = 'Could not remove creatives in line item ' . $lineItemId;
			}
		} catch ( \Exception $e ) {
			$response['success'] = false;
			$response['message'] = $e->getMessage();
		}

		return $response;
	}
}
