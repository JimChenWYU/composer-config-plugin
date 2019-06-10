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
 * Defines class represents output configuration file with constant definitions.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 *
 * @since php5.5
 */
class Defines extends Config
{
    protected function loadFile($path)
    {
        parent::loadFile($path);
        if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
            return [];
        }

        return [$path];
    }

    public function buildRequires()
    {
        $res = [];
        foreach ($this->values as $path) {
            $res[] = "require_once '$path';";
        }

        return implode("\n", $res);
    }

    /**
     * @param string $path
     * @param array  $data
     * @throws \ReflectionException
     * @throws \hiqdev\composer\config\exceptions\FailedWriteException
     */
    protected function writeFile($path, array $data)
    {
        $this->writePhpFile($path, $this->buildRequires(), true, false);
    }
}
