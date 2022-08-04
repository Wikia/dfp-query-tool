<?php

namespace Common\TwigExtension;

use Twig_Extension;

class TwigExtension extends Twig_Extension
{
	public function getFunctions()
	{
		return [
			new \Twig_SimpleFunction('getResourcePath', '\Common\TwigExtension\ResourceLoader::getPath')
		];
	}
}