<?php

namespace Common\TwigExtension;

use Symfony\Component\HttpFoundation\Request;

class ResourceLoader
{
	public static function getPath($namespace) {
		list($bundle, $resource) = explode(':', $namespace);
		$request = Request::createFromGlobals();

		return $request->getBaseUrl() . sprintf('/public/%s/%s', strtolower($bundle), $resource);
	}
}