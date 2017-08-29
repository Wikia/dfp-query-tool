<?php

namespace Inventory\Api;

use Google\AdsApi\Dfp\v201705\Size;
use Google\AdsApi\Dfp\v201705\TemplateCreative;

class CreativeService {
	private $creativeService;

	function __construct() {
		$this->creativeService = DfpService::get(\Google\AdsApi\Dfp\v201705\CreativeService::class);
	}

	public function createFromTemplate($form) {
		list($width, $height) = explode('x', trim($form['sizes']));

		$creative = new TemplateCreative();
		$creative->setName($form['creativeName']);
		$creative->setAdvertiserId($form['advertiserId']);
		$creative->setCreativeTemplateId($form['creativeTemplateId']);
		$creative->setSize(new Size(intval($width), intval($height), false));

		$result = $this->creativeService->createCreatives([$creative]);

		return $result[0]->getId();
	}
}

?>
