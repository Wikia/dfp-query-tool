<?php

namespace Common\Api;

class Authenticator
{
	private static $credentialsPath = __DIR__ . '/../../../config/auth.ini';

	static public function getUser() {
		$user = new \DfpUser(self::$credentialsPath);

		return $user;
	}
}