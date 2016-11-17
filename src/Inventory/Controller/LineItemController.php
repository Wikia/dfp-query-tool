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
		$lineItem = null;
		$lineItemCreativeAssociation = [];
		$message = null;
		$messageType = 'info';
		$form = [];

		if ($request->isMethod('POST')) {
			$form = $request->request->all();
			try {
				$lineItem = $this->lineItemService->create($form);
				$lineItemCreativeAssociation = $this->lineItemCreativeAssociationService->create($form['creativeId'], $lineItem['id']);
				$messageType = 'success';
				$message = 'Line items successfully created.';
			} catch (LineItemException $exception) {
				$message = $exception->getMessage();
				$messageType = 'danger';
			}
		}

		return $this->render('line-item', [
			'action' => $request->getUri(),
			'lineItem' => $lineItem,
			'lica' => $lineItemCreativeAssociation,
			'message' => $message,
			'messageType' => $messageType,
			'form' => json_encode($form)
		]);
	}
}
