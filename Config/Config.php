<?php

namespace Config;

use Symfony\Component\Yaml\Yaml;

/**
 * Config
 *
 * @author Juan Manuel Fernandez <juanmf@gmail.com>
 */
class Config
{
    const CONFIG_FILE = 'config.yml';

    private static $config;
    
    public static function get($param, $default = null)
    {   
        if (empty(self::$config)) {
            self::$config = Yaml::parse(__DIR__ . '/' . self::CONFIG_FILE);
        }
        if (isset(self::$config[$param])) {
            return self::$config[$param];
        } elseif (null !== $default) {
            return $default;
        }
        return null;
    }
}
