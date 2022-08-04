<?php

namespace Inventory\Controller;

use Common\Controller\Controller;
use Inventory\Api\LineItemCreativeAssociationService;
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

		if ($request->isMethod('POST')) {
			$form = $request->request->all();
			$result = $this->lineItemService->processLineItemsData($form);

			$responses = $result['responses'];
			$form = $result['data'];
		}

		return $this->render('line-item', [
			'action' => $request->getUri(),
			'responses' => $responses,
			'form' => json_encode($form)
		]);
	}
}
