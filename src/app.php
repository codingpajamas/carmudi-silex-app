<?php

use Silex\Application;
use Silex\Provider\AssetServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Silex\Provider\DoctrineServiceProvider;

$app = new Application();
$app->register(new ServiceControllerServiceProvider());
$app->register(new AssetServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());
$app['twig'] = $app->extend('twig', function ($twig, $app) {
    // add custom globals, filters, tags, ...

    return $twig;
});

// DB config
$app->register(new DoctrineServiceProvider, [
	'db.options' => [
		'driver' => 'pdo_mysql',
		'host' => '127.0.0.1',
		'dbname' => 'carmudi',
		'user' => 'root',
		'password' => '',
		'port' => 3306
	]
]);

return $app;
