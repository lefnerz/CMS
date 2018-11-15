<?php

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$dir = __DIR__ . '/../../../';

require $dir . 'autoload.php';

$configurator = new Nette\Configurator;

//$configurator->setDebugMode('23.75.345.200'); // enable for your remote IP
$configurator->enableTracy($dir . '../log');

$configurator->setTimeZone('Europe/Prague');
$configurator->setTempDirectory($dir . '../temp');

$configurator->createRobotLoader()
	->addDirectory($dir . '/../app')
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/cms/config/config.neon');
$configurator->addConfig($dir . '../app/config/config.local.neon');

$container = $configurator->createContainer();

return $container;
