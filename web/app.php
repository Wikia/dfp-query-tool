<?php

use Symfony\Component\HttpFoundation\Request;

require_once __DIR__ . '/../vendor/autoload.php';

$app = new Silex\Application();
$reportController = new \Report\Controller\ReportController($app);
$queryController = new \Report\Controller\QueryController($app);
$tableauController = new \Tableau\Controller\TableauController($app);
$lineItemController = new \Inventory\Controller\LineItemController($app);

$app['debug'] = true;

$app->register(new Silex\Provider\TwigServiceProvider(), [
	'twig.path' => [
		__DIR__ . '/../src/Inventory/Resources/templates',
		__DIR__ . '/../src/Tableau/Resources/templates'
	]
]);

$app->extend('twig', function($twig, $app) {
	$twig->addExtension(new \Common\TwigExtension\TwigExtension());

	return $twig;
});

$app->post('/api/query', function(\Symfony\Component\HttpFoundation\Request $request) use ($app, $queryController) {
	return $app->json($queryController->post($request));
});

$app->get('/api/reports/{id}', function($id) use ($app, $reportController) {
	return $app->json($reportController->get($id));
});

$app->get('/tableau', function () use ($tableauController) {
	return $tableauController->renderWebConnector();
});

$app->get('/inventory/line_item', function (Request $request) use ($lineItemController) {
	return $lineItemController->createLineItem($request);
});

$app->post('/inventory/line_item', function (Request $request) use ($lineItemController) {
	return $lineItemController->createLineItem($request);
});

$app->run();