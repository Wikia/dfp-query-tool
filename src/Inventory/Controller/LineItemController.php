<?php

namespace Inventory\Controller;

use Common\Controller\Controller;
use Inventory\Api\LineItemCreativeAssociationService;
use Inventory\Api\LineItemException;
use Inventory\Api\LineItemService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class LineItemController extends Controller
{
	protected $lineItemService;
	protected $lineItemCreativeAssociationService;

	public function __construct(Application $app)
	{
		parent::__construct($app);
		$this->lineItemService = new LineItemService();
		$this->lineItemCreativeAssociationService = new LineItemCreativeAssociationService();
	}

	public function createLineItem(Request $request) {
		$responses = [];
		$form = [];
		$index = 0;

		if ($request->isMethod('POST')) {
			$form = $request->request->all();
			list($isValid, $errorMessages) = $this->validateForm($form);

			if (!$isValid) {
				foreach ($errorMessages as $errorMessage) {
					$responses[] = [
						'messageType' => 'danger',
						'message' => $errorMessage,
						'lineItem' => null,
						'lica' => null
					];
				}
			} else {
				$formsSet = $this->processForm($form);

				foreach($formsSet as $alteredForm) {
					try {
						$lineItem = $this->lineItemService->create($alteredForm);
						$responses[$index]['lineItem'] = $lineItem;
						$responses[$index]['lica'] = $this->lineItemCreativeAssociationService->create($form['creativeId'], $lineItem['id']);
						$responses[$index]['messageType'] = 'success';
						$responses[$index]['message'] = 'Line items successfully created.';
					} catch (LineItemException $exception) {
						$responses[$index]['lineItem'] = null;
						$responses[$index]['message'] = $exception->getMessage();
						$responses[$index]['messageType'] = 'danger';
						$responses[$index]['lica'] = $this->lineItemCreativeAssociationService->getIncorrectLineItemResult();
					}

					$index++;
				}
			}
		}

		return $this->render('line-item', [
			'action' => $request->getUri(),
			'responses' => $responses,
			'form' => json_encode($form)
		]);
	}

	private function processForm($form) {
		$formsSet = [];
		if (!empty($form['iterator'])) {
			$elements = explode(',', $form['iterator']);
			$priceMapElements = explode(',', $form['priceMap']);

			foreach ( $elements as $index => $element ) {
				$alteredForm = $form;

				foreach ( $alteredForm as $key => $value ) {
					$alteredForm[$key] = str_replace( '%%element%%', trim($element), $value );
					if (isset($priceMapElements[$index])) {
						$alteredForm[$key] = str_replace( '%%priceMapElement%%', trim($priceMapElements[$index]), $alteredForm[$key] );
					}
				}
				$formsSet[] = $alteredForm;
			}
		} else {
			$formsSet[] = $form;
		}

		return $formsSet;
	}

	private function validateForm($form) {
		$isValid = true;
		$errors = [];

		if (
			!empty($form['iterator']) && !empty($form['priceMap']) &&
			substr_count($form['iterator'], ',') !== substr_count($form['priceMap'], ',')
		) {
			$isValid = false;
			$errors[] = 'Number of elements and priceMapElements have to be the same';
		}

		return [$isValid, $errors];
	}
}
