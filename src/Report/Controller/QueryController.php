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
        $date = new \DateTime('-1 day', new \DateTimeZone('Europe/Warsaw'));
        return $this->reportService->postQuery($request->request, $date);
	}
}