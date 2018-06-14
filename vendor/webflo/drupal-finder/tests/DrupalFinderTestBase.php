<?php

namespace DrupalFinder\Tests;

use DrupalFinder\DrupalFinder;
use Exception;
use PHPUnit_Framework_TestCase;

abstract class DrupalFinderTestBase extends PHPUnit_Framework_TestCase
{
    /**
     * @var \DrupalFinder\DrupalFinder
     */
    protected $finder;

    protected function setUp()
    {
        parent::setUp();
        $this->finder = new DrupalFinder();
    }

    protected function dumpToFileSystem($fileStructure, $root)
    {
        $fileStructure = $this->prepareFileStructure($fileStructure);
        foreach ($fileStructure as $name => $content) {
            if (is_array($content)) {
                mkdir($root . '/' . $name);
                $this->dumpToFileSystem($content, $root . '/' . $name);
            } else {
                file_put_contents($root . '/' . $name, $content);
            }
        }
    }

    protected function prepareFileStructure($fileStructure)
    {
        foreach ($fileStructure as $name => $content) {
            if (($name === 'composer.json' || $name === 'composer.lock') && is_array($content)) {
                $fileStructure[$name] = json_encode($content, JSON_UNESCAPED_SLASHES);
            } elseif (is_array($content)) {
                $fileStructure[$name] = $this->prepareFileStructure($content);
            }
        }
        return $fileStructure;
    }

    protected function tempdir($dir, $prefix = '', $mode = 0700)
    {
        if (substr($dir, -1) != '/') {
            $dir .= '/';
        }
        do {
            $path = $dir . $prefix . mt_rand(0, 9999999);
        } while (!mkdir($path, $mode));
        register_shutdown_function(
            [get_called_class(), 'tempdir_remove'],
            $path
        );

        return realpath($path);
    }

    public static function tempdir_remove($path)
    {
        if (is_link($path)) {
            if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                rmdir($path);
            } else {
                unlink($path);
            }

            return;
        }

        foreach (scandir($path) as $child) {
            if (in_array($child, ['.', '..'])) {
                continue;
            }
            $child = "$path/$child";
            is_dir($child) ? static::tempdir_remove($child) : unlink($child);
        }
        rmdir($path);
    }

    /**
     * @param $target
     * @param $link
     *
     * @throws \PHPUnit_Framework_SkippedTestError
     */
    protected function symlink($target, $link)
    {
        try {
            return symlink($target, $link);
        } catch (Exception $e) {
            if (defined('PHP_WINDOWS_VERSION_BUILD')
                && strstr($e->getMessage(), WIN_ERROR_PRIVILEGE_NOT_HELD)
            ) {
                $this->markTestSkipped(<<<'MESSAGE'
No privilege to create symlinks. Run test as Administrator (elevated process).
MESSAGE
                );
            }
            throw $e;
        }
    }
}

define('WIN_ERROR_PRIVILEGE_NOT_HELD', '1314');
