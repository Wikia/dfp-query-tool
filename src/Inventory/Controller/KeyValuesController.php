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
			$values = [];
			if (isset($form['combinationValues'])) {
				$values = $this->getValues($form['combinationValues']);
			}

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

	private function getValues($combinations) {
		$parsedCombinations = [];
		foreach ($combinations as $combination) {
			if (trim($combination) === '') {
				continue;
			}

			$parsedCombinations[] = explode(',', $combination);
		}

		return $this->generateCombinations($parsedCombinations);
	}

	private function generateCombinations($arrays, $i = 0) {
		if (!isset($arrays[$i])) {
			return array();
		}
		if ($i == count($arrays) - 1) {
			return $arrays[$i];
		}

		$tmp = $this->generateCombinations($arrays, $i + 1);

		$result = array();
		foreach ($arrays[$i] as $v) {
			foreach ($tmp as $t) {
				$result[] = implode('', [$v, $t]);
			}
		}

		return $result;
	}
}
