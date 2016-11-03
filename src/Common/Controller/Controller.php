<?php

namespace Common\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Controller
{
	protected $app;

	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	protected function render($template, $parameters = []) {
		$request = Request::createFromGlobals();

		return $this->app['twig']->render($template . '.twig', array_merge(
			$parameters,
			[
				'request' => $request
			]
		));
	}
}