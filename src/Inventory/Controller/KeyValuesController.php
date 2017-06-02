<?php

namespace Inventory\Controller;

use Common\Controller\Controller;
use Inventory\Api\CustomTargetingService;
use Inventory\Api\LineItemCreativeAssociationService;
use Inventory\Api\LineItemException;
use Inventory\Api\LineItemService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class KeyValuesController extends Controller
{
	protected $customTargetingService;

	public function __construct(Application $app)
	{
		parent::__construct($app);
		$this->customTargetingService = new CustomTargetingService();
	}

	public function createKeyValues(Request $request)
	{
		$addedValues = 0;
		$error = '';
		$form = [];

		if ($request->isMethod('POST')) {
			$form = $request->request->all();
			$key = $form['key'];
			$values = explode("\n", $form['values']);

			try {
				$addedValues = $this->customTargetingService->addValues($key, $values);
			} catch (\Exception $exception) {
				$error = $exception->getMessage();
			}
		}

		return $this->render('key-values', [
			'action' => $request->getUri(),
			'addedValues' => $addedValues,
			'error' => $error,
			'form' => json_encode($form)
		]);
	}
}
