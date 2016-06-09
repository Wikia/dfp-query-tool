<?php

require_once __DIR__.'/vendor/autoload.php';

function GetOAuth2Credential($user) {
	$redirectUri = NULL;
	$offline = TRUE;

	$OAuth2Handler = $user->GetOAuth2Handler();
	$authorizationUrl = $OAuth2Handler->GetAuthorizationUrl($user->GetOAuth2Info(), $redirectUri, $offline);

	printf("Log in to your DFP account and open the following URL:\n%s\n\n", $authorizationUrl);
	print "After approving the token enter the authorization code here: ";
	$stdin = fopen('php://stdin', 'r');
	$code = trim(fgets($stdin));
	fclose($stdin);
	print "\n";

	$user->SetOAuth2Info($OAuth2Handler->GetAccessToken($user->GetOAuth2Info(), $code, $redirectUri));

	return $user->GetOAuth2Info();
}

if (__FILE__ != realpath($_SERVER['PHP_SELF'])) {
	return;
}

try {
	$user = new DfpUser(__DIR__ . '/config/auth.ini');
	$user->LogAll();

	$oauth2Info = GetOAuth2Credential($user);

	printf("Your refresh token is: %s\n\n", $oauth2Info['refresh_token']);
	printf("In your auth.ini file, edit the refresh_token line to be:\n"
		. "refresh_token = \"%s\"\n", $oauth2Info['refresh_token']);
} catch (OAuth2Exception $e) {
	printf("%s\n", $e->getMessage());
} catch (ValidationException $e) {
	printf("%s\n", $e->getMessage());
} catch (Exception $e) {
	printf("An error has occurred: %s\n", $e->getMessage());
}