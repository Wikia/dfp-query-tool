<?php

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();

$app->get('/api/reports', function() use ($app) {
	$controller = new \Report\Controller\ReportController($app);
	return $app->json($controller->generate());
});

$app->get('/api/reports/{id}', function($id) use ($app) {
	$controller = new \Report\Controller\ReportController($app);
	return $app->json($controller->get($id));
});

$app->get('/tableau', function () use ($app) {
	$controller = new \Tableau\Controller\TableauController($app);
	return $controller->webConnector();
});

$app['debug'] = true;

$app->run();