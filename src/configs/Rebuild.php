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
 * Rebuild class represents __rebuild.php script.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 *
 * @since php5.5
 */
class Rebuild extends Config
{
    /**
     * @param string $path
     * @param array  $data
     * @throws \hiqdev\composer\config\exceptions\FailedWriteException
     */
    protected function writeFile($path, array $data)
    {
        static::putFile($path, file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . '__rebuild.php'));
    }
}
