<?php

namespace Inventory\Controller;

use Common\Controller\Controller;
use Inventory\Api\CreativeService;
use Inventory\Api\LineItemCreativeAssociationService;
use Inventory\Api\LineItemException;
use Inventory\Api\LineItemService;
use Inventory\Api\OrderService;
use Inventory\Form\LineItemForm;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends Controller
{
	protected $orderService;
	protected $creativeService;
	protected $lineItemService;
	protected $lineItemCreativeAssociationService;

	public function __construct(Application $app)
	{
		parent::__construct($app);
		$this->orderService = new OrderService();
		$this->creativeService = new CreativeService();
		$this->lineItemService = new LineItemService();
		$this->lineItemCreativeAssociationService = new LineItemCreativeAssociationService();
	}

	public function createOrder(Request $request) {
		$responses = [];
		$form = [];

		if ($request->isMethod('POST')) {
			$form = $request->request->all();

			$alteredForm = $this->processForm($form);

			foreach($alteredForm as $orderData) {
				$orderId = $this->orderService->create($orderData);
				$responses[] = [
					'messageType' => 'success',
					'message' => 'Order successfully created.'
				];

				$creativeId = $this->creativeService->createFromTemplate($orderData);
				$responses[] = [
					'messageType' => 'success',
					'message' => 'Creative successfully created.'
				];

				$orderData['orderId'] = $orderId;
				$orderData['creativeId'] = $creativeId;
				$lineItemForm = new LineItemForm($orderData);

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

					foreach($formsSet as $alteredLineItemForm) {
						try {
							$lineItem = $this->lineItemService->create($alteredLineItemForm);
							$responses[] = [
								'lineItem' => $lineItem,
								'lica' => $this->lineItemCreativeAssociationService->create($alteredLineItemForm['creativeId'], $lineItem['id']),
								'messageType' => 'success',
								'message' => 'Line items successfully created.'
							];
						} catch (LineItemException $exception) {
							$responses[] = [
								'lineItem' => null,
								'lica' => $exception->getMessage(),
								'messageType' => 'danger',
								'message' => $this->lineItemCreativeAssociationService->getIncorrectLineItemResult()
							];
						}
					}
				}
			}
		}

		return $this->render('order', [
			'action' => $request->getUri(),
			'responses' => $responses,
			'form' => json_encode($form)
		]);
	}

	private function processForm($form) {
		$formsSet = [];
		if (!empty($form['sizeIterator'])) {
			$sizeElements = explode(',', $form['sizeIterator']);

			foreach ( $sizeElements as $index => $element ) {
				$alteredForm = $form;

				foreach ( $alteredForm as $key => $value ) {
					$alteredForm[$key] = str_replace( '%%sizeElement%%', trim($element), $value );
				}
				$formsSet[] = $alteredForm;
			}
		} else {
			$formsSet[] = $form;
		}

		return $formsSet;
	}
}
