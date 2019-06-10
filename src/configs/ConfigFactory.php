<?php
/**
 * Composer plugin for config assembling
 *
 * @link      https://github.com/hiqdev/composer-config-plugin
 * @package   composer-config-plugin
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2016-2018, HiQDev (http://hiqdev.com/)
 */

namespace hiqdev\composer\config\configs;

use hiqdev\composer\config\Builder;

/**
 * Config factory creates Config object of proper class
 * according to config name (and mayby other options later).
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 *
 * @since php5.5
 */
class ConfigFactory
{
    private static $knownTypes = [
        '__rebuild'     => Rebuild::class,
        '__files'       => System::class,
        'aliases'       => System::class,
        'packages'      => System::class,
        'dotenv'        => DotEnv::class,
        'params'        => Params::class,
        'defines'       => Defines::class,
    ];

    /**
     * @param Builder $builder
     * @param string $name
     * @return Config
     */
    public static function create(Builder $builder, $name)
    {
//        $class = static::$knownTypes[$name] ?? Config::class;
        $class = isset(static::$knownTypes[$name]) ? static::$knownTypes[$name] : Config::class;

        return new $class($builder, $name);
    }
}
