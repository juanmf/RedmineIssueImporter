<?php
require_once ('autoload.php');

use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new \Command\ImportCommand());
$application->add(new \Command\deleteCommand());
$application->run();
