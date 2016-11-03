<?php

namespace Tableau\Controller;

use Common\Controller\Controller;

class TableauController extends Controller
{
	public function renderWebConnector() {
		return $this->render('tableau-web-connector');
	}
}