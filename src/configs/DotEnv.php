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

/**
 * DotEnv class represents output configuration file with ENV values.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 *
 * @since php5.5
 */
class DotEnv extends Config
{
    /**
     * @param string $path
     * @param array  $data
     * @throws \ReflectionException
     * @throws \hiqdev\composer\config\exceptions\FailedWriteException
     */
    protected function writeFile($path, array $data)
    {
        $this->writePhpFile($path, $data, false, false);
    }
}
