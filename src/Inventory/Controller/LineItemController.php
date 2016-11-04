<?php

namespace Inventory\Controller;

use Common\Controller\Controller;
use Inventory\Api\LineItemException;
use Inventory\Api\LineItemService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class LineItemController extends Controller
{
	protected $lineItemService;

	public function __construct(Application $app)
	{
		parent::__construct($app);
		$this->lineItemService = new LineItemService();
	}

	public function createLineItem(Request $request) {
		$message = null;
		$messageType = 'info';
		$form = [];

		if ($request->isMethod('POST')) {
			$form = $request->request->all();
			try {
				$this->lineItemService->create($form);
				$messageType = 'success';
				$message = 'Line item successfully created';
			} catch (LineItemException $exception) {
				$message = $exception->getMessage();
				$messageType = 'danger';
			}
		}

		return $this->render('line-item', [
			'message' => $message,
			'messageType' => $messageType,
			'form' => json_encode($form)
		]);
	}
}