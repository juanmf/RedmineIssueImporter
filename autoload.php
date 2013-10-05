<?php

$loader = require_once ('vendor/autoload.php');

/* @var $loader Composer\Autoload\ClassLoader */
$paths = array(
    'Config' => array(__DIR__ ),
    'Parsers' => array(__DIR__ ),
    'EntityPopulator' => array(__DIR__),
    'Transformers' => array(__DIR__),
    'Command' => array(__DIR__),
    '' => array(__DIR__),
);

foreach ($paths as $prefix => $path) {
    $loader->set($prefix, $path);
}

//ini_set('xdebug.var_display_max_data', '99999');
//die(var_dump($loader->getPrefixes()));

return $loader;
