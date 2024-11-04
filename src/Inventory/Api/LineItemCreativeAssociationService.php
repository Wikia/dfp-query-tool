<?php

namespace Inventory\Api;

use Google\AdsApi\AdManager\Util\v202408\StatementBuilder;
use Google\AdsApi\AdManager\v202408\DeleteLineItemCreativeAssociations;
use Google\AdsApi\AdManager\v202408\LineItemCreativeAssociation;
use Google\AdsApi\AdManager\v202408\DeactivateLineItemCreativeAssociations;
use Google\AdsApi\AdManager\v202408\Size;


class LineItemCreativeAssociationService {
	private $customTargetingService;
	private $creativeService;

	public function __construct() {
		$this->customTargetingService = new CustomTargetingService();
		$this->creativeService = new CreativeService();
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

		try {
			if ( empty($lineItemId) ) {
				return $this->getIncorrectLineItemResult();
			} else {
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
				}

				$lica = null;
				for ($i = 0; $i < 10; $i++) {
					$lica = $lineItemCreativeAssociationService->createLineItemCreativeAssociations( $lineItemCreativeAssociations );

					if ($lica || isset($lica)) {
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

		return $response;
	}

	private function getOverrideSizes($sizeList) {
		$sizesOverride = [];
		$sizes = explode(',', $sizeList);

		foreach ($sizes as $size) {
			list($width, $height) = explode('x', trim($size));
			$sizesOverride[] = new Size(intval($width), intval($height), false);
		}

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

	public function getLineItemCreativeAssociations( $lineItemId): \Google\AdsApi\AdManager\v202408\LineItemCreativeAssociationPage {
		$lineItemCreativeAssociationService = AdManagerService::get(\Google\AdsApi\AdManager\v202408\LineItemCreativeAssociationService::class);

		$statementBuilder = (new StatementBuilder())
			->where('lineItemId = :lineItemId')
			->withBindVariableValue('lineItemId', $lineItemId);
		$associations = $lineItemCreativeAssociationService->getLineItemCreativeAssociationsByStatement($statementBuilder->toStatement());

		return $associations;
	}

	public function overrideAllLineItemCreativeSizes($lineItemId, $sizes) {
		$associations = $this->getLineItemCreativeAssociations($lineItemId);

		$overrideSizes = $this->getOverrideSizes($sizes);

		$associationResults = $associations->getResults();

		if ($associationResults !== null) {
			$updatedAssociations = [];

			foreach ($associations->getResults() as $association) {
				// Modify the line item association size.
				// This changes the override sizes in the line item to creative screen in GAM
				$association->setSizes($overrideSizes);

				// Set the updated association object in the updatedAssociations array,
				// so that it can be updated in one batched API call later down the line.
				$updatedAssociations[] = $association;
			}
		}

		// If there were updated associations, update them in one batched API call
		if (!empty($updatedAssociations)) {
			$lineItemCreativeAssociationService = AdManagerService::get(\Google\AdsApi\AdManager\v202408\LineItemCreativeAssociationService::class);

			try {
				// Use the GAM API to update all the associations that were updated and placed in the updatedAssociations array
				$result = $lineItemCreativeAssociationService->updateLineItemCreativeAssociations( $updatedAssociations );

				printf("Updated %s/%s line item associations for lineItem %s \n", count($result), count($associationResults), $lineItemId);
				foreach ( $result as $updatedAssociation ) {
					printf( "Updated association for creative ID '%d' with line item ID '%d'.\n",
						$updatedAssociation->getCreativeId(),
						$updatedAssociation->getLineItemId());
				}
			} catch ( \Exception $e ) {
				printf( "Error updating size override associations for line item %s: %s\n", $lineItemId, $e->getMessage() );
			}
		}

	}

}
