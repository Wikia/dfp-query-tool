<?php

namespace Inventory\Api;

use Common\Api\Authenticator;
use Google\AdsApi\AdManager\AdManagerServices;

class AdManagerService
{
	public static function get($serviceName) {
		$adManagerServices = new AdManagerServices();
		$session = Authenticator::getSession();

		return $adManagerServices->get($session, $serviceName);
	}
}
