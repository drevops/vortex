<?php

namespace derhasi\Composer\Tests;

use derhasi\Composer\PathPreserver;
use Composer\Util\Filesystem;
use Composer\Package\Package;
use Composer\Package\RootPackage;
use Composer\Composer;
use Composer\Config;
use derhasi\tempdirectory\TempDirectory;

/**
 * Test for path preserver functionality.
 */
class PathPreserverTest extends \PHPUnit_Framework_TestCase
{

    /**
     * set up test environmemt
     */
    public function setUp()
    {
        $this->fs = new Filesystem();
        $this->io = $this->getMock('Composer\IO\IOInterface');
    }

    /**
     * Tests that the directory is created
     */
    public function testPreserveAndRollback()
    {
        $workingDirectory = new TempDirectory(__METHOD__);
        $cacheDirectory = new TempDirectory(__METHOD__.'-cache');

        // Create directory to test.
        $folder1 = $workingDirectory->getPath('folder1');
        mkdir($folder1);
        $file1 = $workingDirectory->getPath('file1.txt');
        file_put_contents($file1, 'Test content');

        // We simulate creation of
        $installPaths = array(
            $workingDirectory->getRoot(),
        );

        $preservePaths = array(
            $folder1,
            $file1,
        );

        $preserver = new PathPreserver($installPaths, $preservePaths, $cacheDirectory->getRoot(), $this->fs, $this->io);
        $this->assertIsDir($folder1, 'Folder created.');
        $this->assertFileExists($file1, 'File created.');
        $preserver->preserve();
        $this->assertFileNotExists($folder1, 'Folder removed for backup.');
        $this->assertFileNotExists($file1, 'File was removed for backup.');

        $preserver->rollback();
        $this->assertIsDir($folder1, 'Folder recreated.');
        $this->assertFileExists($file1, 'File recreated.');
    }

    /**
     * Tests file_exists() restrictions on non executable directories.
     */
    public function testFileExists()
    {
        $workingDirectory = new TempDirectory(__METHOD__);

        $folder1 = $workingDirectory->getPath('folder1');
        $subfolder1 = $workingDirectory->getPath('folder1/subfolder1');
        $file1 = $workingDirectory->getPath('folder1/subfolder1/file1.txt');
        $file2 = $workingDirectory->getPath('folder1/file2.txt');

        mkdir($folder1);
        mkdir($subfolder1);
        file_put_contents($file1, '');
        file_put_contents($file2, '');

        $this->assertIsDir($folder1);
        $this->assertIsDir($subfolder1);
        $this->assertFileExists($file1);
        $this->assertFileExists($file2);

        // After chaning the file mode of the parent directory, no containing files
        // or folders can be found anymore.
        chmod($folder1, 0400);

        $this->assertTrue(file_exists($folder1), 'Folder is still present.');
        $this->assertFalse(file_exists($subfolder1), 'File exists retures FALSE for subfolder');
        $this->assertFalse(file_exists($file1), 'File exists retures FALSE for subfolder');
        $this->assertFalse(file_exists($file2), 'File exists retures FALSE for subfolder');
    }

    /**
     * Tests preservation and rollback on tricky path permissions.
     *
     * @depends testPreserveAndRollback
     */
    public function testFileModes()
    {
        $workingDirectory = new TempDirectory(__METHOD__);
        $cacheDirectory = new TempDirectory(__METHOD__.'-cache');

        // Create directory to test.
        $folder1 = $workingDirectory->getPath('folder1');
        mkdir($folder1);

        $subfolder1 = $workingDirectory->getPath('folder1/subfolder1');
        mkdir($subfolder1);

        $file1 = $workingDirectory->getPath('folder1/file1.txt');
        file_put_contents($file1, 'Test content');
        $file2 = $workingDirectory->getPath('folder1/file2.txt');
        file_put_contents($file2, 'Test content 2');

        // After changing some permissions we test if the given paths can be
        // restored.
        chmod($file2, 0400);
        // For checking if file exists, we need to set the permission for the folder
        // differently
        // @see http://stackoverflow.com/questions/11834629/glob-lists-files-file-exists-says-they-dont-exist
        chmod($folder1, 0500);

        $this->assertIsDir($folder1, 'Folder created.');
        $this->assertIsDir($subfolder1, 'Subfolder 1 created.');
        $this->assertFileExists($file1, 'File 1 created.');
        $this->assertFileExists($file2, 'File 2 created.');

        $installPaths = array(
            $folder1,
        );
        $preservePaths = array(
            $subfolder1,
            $file1,
            $file2,
        );

        $preserver = new PathPreserver($installPaths, $preservePaths, $cacheDirectory->getRoot(), $this->fs, $this->io);

        // We check if preservation works even with restrictive permissions.
        chmod($folder1, 0400);
        $preserver->preserve();
        chmod($folder1, 0500);

        $this->assertIsDir($folder1, 'Folder not removed for backup.');
        $this->assertFileNotExists($subfolder1, 'Subfolder removed for backup.');
        $this->assertFileNotExists($file1, 'File 1 was removed for backup.');
        $this->assertFileNotExists($file2, 'File 2 was removed for backup.');

        // We check if rollback works even with restrictive permissions.
        chmod($folder1, 0400);
        $preserver->rollback();
        chmod($folder1, 0500);

        $this->assertFileExists($subfolder1, 'Subfolder 1 recreated.');
        $this->assertFileExists($file1, 'File 1 recreated.');
        $this->assertFileExists($file2, 'File 2 recreated.');
    }

    /**
     * Custom assertion for existing directory.
     *
     * @param $path
     * @param string $message
     */
    protected function assertIsDir($path, $message = '')
    {
        $this->assertTrue(file_exists($path) && is_dir($path), $message);
    }
}
