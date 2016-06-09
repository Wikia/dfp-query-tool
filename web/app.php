<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$reportController = new \Report\Controller\ReportController($app);
$tableauController = new \Tableau\Controller\TableauController($app);

$app->post('/api/reports', function() use ($app, $reportController) {
	return $app->json($reportController->generate());
});

$app->get('/api/reports/{id}', function($id) use ($app, $reportController) {
	return $app->json($reportController->get($id));
});

$app->get('/tableau', function () use ($tableauController) {
	return $tableauController->renderWebConnector();
});

$app['debug'] = true;

$app->run();