<?php

namespace Common\Api;

use Google\AdsApi\Common\OAuth2TokenBuilder;
use Google\AdsApi\AdManager\AdManagerSessionBuilder;

class Authenticator
{
	private static $credentialsPath = __DIR__ . '/../../../config/auth.ini';

	static public function getSession() {
		$oAuth2Credential = (new OAuth2TokenBuilder())
			->fromFile(self::$credentialsPath)
			->build();

		return (new AdManagerSessionBuilder())
			->fromFile(self::$credentialsPath)
			->withOAuth2Credential($oAuth2Credential)
			->build();
	}
}
