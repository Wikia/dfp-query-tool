<?php

namespace Common\Controller;

use Silex\Application;

class Controller
{
	protected $app;

	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	protected function render($namespace) {
		list($bundle, $template) = explode(':', $namespace);
		$path = __DIR__ . '/../../' . $bundle . '/Resources/templates/' . $template . '.html';

		return file_get_contents($path);
	}
}