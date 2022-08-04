<?php

namespace Inventory\Form;

class LineItemForm {

	private $data;

	public function __construct($formData) {
		$this->data = $formData;
	}

	public function process() {
		$formsSet = [];
		if (!empty($this->data['iterator'])) {
			$elements = explode(',', $this->data['iterator']);

			if (isset($this->data['priceMap'])) {
				$priceMapElements = explode(',', $this->data['priceMap']);
			}

			foreach ( $elements as $index => $element ) {
				$alteredForm = $this->data;

				foreach ( $alteredForm as $key => $value ) {
					$alteredForm[$key] = str_replace( '%%element%%', trim($element), $value );
					if (isset($priceMapElements[$index])) {
						$alteredForm[$key] = str_replace( '%%priceMapElement%%', trim($priceMapElements[$index]), $alteredForm[$key] );
					}
				}
				$formsSet[] = $alteredForm;
			}
		} else {
			$formsSet[] = $this->data;
		}

		return $formsSet;
	}

	public function validate() {
		$isValid = true;
		$errors = [];

		if (
			!empty($this->data['iterator']) && !empty($this->data['priceMap']) &&
			substr_count($this->data['iterator'], ',') !== substr_count($this->data['priceMap'], ',')
		) {
			$isValid = false;
			$errors[] = 'Number of elements and priceMapElements have to be the same';
		}

		return [$isValid, $errors];
	}
}
