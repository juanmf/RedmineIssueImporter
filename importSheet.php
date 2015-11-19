<?php
require_once ('autoload.php');
require_once ('error_handler.php');

use Symfony\Component\Console\Application;
use \Config\Config;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

$container = new ContainerBuilder();
$loader = new YamlFileLoader($container, new FileLocator(__DIR__ . "/Config/Services"));
$loader->load('services.yml');
Config::setContainer($container);

$application = new Application();

$application->setDispatcher(Config::getContainer()->get('dispatcher'));

$application->add(new \Command\ImportCommand());
$application->add(new \Command\DeleteCommand());
$application->add(new \Command\UpdateCommand());



$application->run();
