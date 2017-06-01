<?php

namespace Inventory\Api;

use Common\Api\Authenticator;
use Google\AdsApi\Dfp\DfpServices;

class DfpService
{
	public static function get($serviceName) {
		$dfpServices = new DfpServices();
		$session = Authenticator::getSession();

		return $dfpServices->get($session, $serviceName);
	}
}