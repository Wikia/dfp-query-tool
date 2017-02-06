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

				$s = curl_init();
				curl_setopt($s, CURLOPT_URL, "http://localhost:26300/emit/id1/success");
				curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
				$content = curl_exec($s);
				$status = curl_getinfo($s, CURLINFO_HTTP_CODE);
				curl_close($s);

				$index++;
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
		if ( !empty($form['iterator']) ) {
			$elements = explode(',', $form['iterator']);
			foreach ( $elements as $element ) {
				$alteredForm = $form;

				foreach ( $alteredForm as $key => $value ) {
					$alteredForm[$key] = str_replace( '%%element%%', trim($element), $value );
				}
				$formsSet[] = $alteredForm;
			}
		} else {
			$formsSet[] = $form;
		}

		return $formsSet;
	}
}
