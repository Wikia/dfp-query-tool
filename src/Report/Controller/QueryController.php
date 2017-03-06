<?php

namespace Report\Controller;

use Common\Controller\Controller;
use Report\Api\ReportService;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class QueryController extends Controller
{
	protected $reportService;

	public function __construct(Application $app)
	{
		parent::__construct($app);
		$this->reportService = new ReportService();
	}

	public function post(Request $request) {
		return $this->reportService->postQuery($request->request, new \DateTimeZone('Europe/Warsaw'));
	}
}