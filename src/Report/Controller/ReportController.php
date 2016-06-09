<?php

namespace Report\Controller;

use Common\Controller\Controller;
use Report\Api\ReportService;
use Silex\Application;

class ReportController extends Controller
{
	protected $reportService;

	public function __construct(Application $app)
	{
		parent::__construct($app);
		$this->reportService = new ReportService();
	}

	public function generate() {
		return $this->reportService->generate();
	}

	public function get($id) {
		return $this->reportService->get($id);
	}
}