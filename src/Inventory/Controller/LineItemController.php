<?php

namespace Inventory\Controller;

use Common\Controller\Controller;
use Inventory\Api\LineItemCreativeAssociationService;
use Inventory\Api\LineItemException;
use Inventory\Api\LineItemService;
use Inventory\Form\LineItemForm;
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
			$lineItemForm = new LineItemForm($form);

			list($isValid, $errorMessages) = $lineItemForm->validate();

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
				$formsSet = $lineItemForm->process();

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
}
