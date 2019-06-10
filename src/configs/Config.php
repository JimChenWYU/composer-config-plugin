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
use hiqdev\composer\config\exceptions\FailedWriteException;
use hiqdev\composer\config\Helper;
use hiqdev\composer\config\readers\ReaderFactory;

/**
 * Config class represents output configuration file.
 *
 * @author Andrii Vasyliev <sol@hiqdev.com>
 *
 * @since php5.5
 */
class Config
{
    const UNIX_DS = '/';
    const BASE_DIR_MARKER = '<<<base-dir>>>';

    /**
     * @var string config name
     */
    protected $name;

    /**
     * @var array sources - paths to config source files
     */
    protected $sources = [];

    /**
     * @var array config value
     */
    protected $values = [];

    /**
     * @var Builder
     */
    protected $builder;

    /**
     * Config constructor.
     * @param Builder $builder
     * @param string  $name
     */
    public function __construct(Builder $builder, $name)
    {
        $this->builder = $builder;
        $this->name = $name;
    }

    public function getBuilder()
    {
        return $this->builder;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getValues()
    {
        return $this->values;
    }

    public function load(array $paths = [])
    {
        $configs = [];
        foreach ($paths as $path) {
            $config = $this->loadFile($path);
            if (!empty($config)) {
                $configs[] = $config;
            }
        }

        $this->sources = $configs;

        return $this;
    }

    /**
     * Reads config file.
     * @param string $path
     * @return array configuration read from file
     */
    protected function loadFile($path)
    {
        $reader = ReaderFactory::get($this->builder, $path);

        return $reader->read($path);
    }

    /**
     * Merges given configs and writes at given name.
     * @return Config
     */
    public function build()
    {
        $this->values = $this->calcValues($this->sources);

        return $this;
    }

    public function write()
    {
        $this->writeFile($this->getOutputPath(), $this->values);

        return $this;
    }

    protected function calcValues(array $sources)
    {
        $values = call_user_func_array([Helper::class, 'mergeConfig'], $sources);

        return $this->substituteOutputDirs($values);
    }

    /**
     * @param string $path
     * @param array  $data
     * @throws FailedWriteException
     * @throws \ReflectionException
     */
    protected function writeFile($path, array $data)
    {
        $this->writePhpFile($path, $data, true, true);
    }

    /**
     * Writes complete PHP config file by full path.
     * @param string       $path
     * @param string|array $data
     * @param bool         $withEnv
     * @param bool         $withDefines
     * @throws FailedWriteException
     * @throws \ReflectionException
     */
    protected function writePhpFile($path, $data, $withEnv, $withDefines)
    {
        static::putFile($path, $this->replaceMarkers(implode("\n\n", array_filter([
                'header'  => '<?php',
                'baseDir' => '$baseDir = dirname(dirname(dirname(__DIR__)));',
                'BASEDIR' => "defined('COMPOSER_CONFIG_PLUGIN_BASEDIR') or define('COMPOSER_CONFIG_PLUGIN_BASEDIR', \$baseDir);",
                'dotenv'  => $withEnv ? "\$_ENV = array_merge((array) require __DIR__ . '/dotenv.php', (array) \$_ENV);" : '',
                'defines' => $withDefines ? $this->builder->getConfig('defines')->buildRequires() : '',
                'content' => is_array($data) ? $this->renderVars($data) : $data,
            ]))) . "\n");
    }

    /**
     * @param array $vars array to be exported
     * @return string
     * @throws \ReflectionException
     */
    protected function renderVars(array $vars)
    {
        return 'return ' . Helper::exportVar($vars) . ';';
    }

    /**
     * @param string $content
     * @return string
     */
    protected function replaceMarkers($content)
    {
        $content = str_replace("'" . static::BASE_DIR_MARKER, "\$baseDir . '", $content);

        return str_replace("'?" . static::BASE_DIR_MARKER, "'?' . \$baseDir . '", $content);
    }

    /**
     * Writes file if content changed.
     * @param string $path
     * @param string $content
     * @throws FailedWriteException
     */
    protected static function putFile($path, $content)
    {
        if (file_exists($path) && $content === file_get_contents($path)) {
            return;
        }
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        if (false === file_put_contents($path, $content)) {
            throw new FailedWriteException("Failed write file $path");
        }
    }

    /**
     * Substitute output paths in given data array recursively with marker.
     * @param array $data
     * @return array
     */
    public function substituteOutputDirs(array $data)
    {
        $dir = static::normalizePath(dirname(dirname(dirname($this->getOutputDir()))));

        return static::substitutePaths($data, $dir, static::BASE_DIR_MARKER);
    }

    /**
     * Normalizes given path with given directory separator.
     * Default forced to Unix directory separator for substitutePaths to work properly in Windows.
     * @param string $path path to be normalized
     * @param string $ds directory separator
     * @return string
     */
    public static function normalizePath($path, $ds = self::UNIX_DS)
    {
        return rtrim(strtr($path, '/\\', $ds . $ds), $ds);
    }

    /**
     * Substitute all paths in given array recursively with alias if applicable.
     * @param array $data
     * @param string $dir
     * @param string $alias
     * @return array
     */
    public static function substitutePaths($data, $dir, $alias)
    {
        foreach ($data as &$value) {
            if (is_string($value)) {
                $value = static::substitutePath($value, $dir, $alias);
            } elseif (is_array($value)) {
                $value = static::substitutePaths($value, $dir, $alias);
            }
        }

        return $data;
    }

    /**
     * Substitute path with alias if applicable.
     * @param string $path
     * @param string $dir
     * @param string $alias
     * @return string
     */
    protected static function substitutePath($path, $dir, $alias)
    {
        $end = $dir . self::UNIX_DS;
        $skippable = 0 === strncmp($path, '?', 1) ? '?' : '';
        if ($skippable) {
            $path = substr($path, 1);
        }
        $result = (substr($path, 0, strlen($dir)) === $dir) ? $alias . substr($path, strlen($dir) - 1) : $path;
        if ($path === $dir) {
            $result = $alias;
        } elseif (substr($path, 0, strlen($end)) === $end) {
            $result = $alias . substr($path, strlen($end) - 1);
        } else {
            $result = $path;
        }

        return $skippable . $result;
    }

    public function getOutputDir()
    {
        return $this->builder->getOutputDir();
    }

    /**
     * @param string|null $name
     * @return string
     */
    public function getOutputPath($name = null)
    {
        return $this->builder->getOutputPath($name ?: $this->name);
    }
}
