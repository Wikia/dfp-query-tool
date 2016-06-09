<?php

namespace Tableau\Controller;

use Common\Controller\Controller;

class TableauController extends Controller
{
	public function renderWebConnector() {
		return file_get_contents(__DIR__ . '/../Resources/public/tableau-web-connector.html');
	}
}