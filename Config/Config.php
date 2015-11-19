<?php

namespace Config;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Config
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Config
{
    const CONFIG_FILE = 'config.yml';

    /**
     * Parsed Config/config.yml
     * 
     * @var array 
     */
    private static $config;
    
    /**
     *
     * @var ContainerInterface
     */
    private static $container;
    
    public static function get($param, $default = null)
    {   
        if (empty(self::$config)) {
            self::$config = Yaml::parse(file_get_contents(__DIR__ . '/' . self::CONFIG_FILE));
        }
        if (isset(self::$config[$param])) {
            return self::$config[$param];
        } elseif (null !== $default) {
            return $default;
        }
        return null;
    }

    public static function getContainer() {
        return self::$container;
    }

    public static function setContainer(ContainerInterface $container) {
        self::$container = $container;
    }

    public static function getEntityFactory($presistenceEngine) {
        return self::$container->get($presistenceEngine . "_factory");
    }

}
