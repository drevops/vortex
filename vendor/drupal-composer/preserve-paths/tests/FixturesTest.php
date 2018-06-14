<?php

namespace derhasi\Composer\Tests;

use derhasi\tempdirectory\TempDirectory;

/**
 * Tests for some examples.
 */
class FixturesTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var string
     */
    protected $composerBin;

    /**
     * @var string
     */
    protected $fixturesRoot;

    /**
     * @var string
     */
    protected $projectRoot;

    /**
     * Set up test.
     */
    protected function setUp()
    {
        $this->projectRoot = realpath(__DIR__.'/..');
        $this->composerBin = realpath(__DIR__.'/../vendor/bin/composer');
        $this->fixturesRoot = realpath(__DIR__.'/fixtures');
    }

    /**
     * Test provided fixtures.
     *
     * @param string $folder
     *   Name of the folder of the fixture
     * @param array  $commands
     *   Array of composer commands to process
     * @param array  $files
     *   Array of files to check for existance
     *
     * @dataProvider fixturesProvider
     */
    public function testFixtures($folder, $commands = array(), $files = array())
    {
        $workingDirectory = new TempDirectory(__METHOD__.$folder);

        chdir($workingDirectory->getRoot());
        copy($this->fixturesRoot.'/example/composer.json', $workingDirectory->getRoot().'/composer.json');

        // Add this project as local development repository sow we work with
        // the latest code.
        $this->composer('config', 'repositories.dev', 'path', $this->projectRoot);
        
        $output = $this->composer('install');

        // Check for deprecation notices.
        $this->assertDeprecationNotice($output);

        // Run additional composer commands.
        foreach ($commands as $command) {
            call_user_func_array(array($this, 'composer'), $command);
        }
        
        // Check for file existance.
        foreach ($files as $file) {
            $this->assertFileExists($file);
        }
        
        unset($workingDirectory);
    }

    /**
     * Provides fixtures test data.
     *
     * @return array
     */
    public function fixturesProvider()
    {
        return array(
            array(
                'example',
                // Update drupal/drupal to the newest release
                array(
                    array('update', 'drupal/drupal'),
                ),
                array( 'web/index.php', 'web/sites/all/modules/contrib/views/views.module'),
            ),
        );
    }
    
    /**
     * Run composer command.
     *
     * @param string $command
     * @param string $arg,... Optional arguments
     *
     * @return string[]
     *   Array of output lines by the composer command.
     */
    protected function composer($command)
    {
        $exec = $this->composerBin;
        $exec .= ' '.escapeshellcmd($command);
        $args = func_get_args();
        array_shift($args);
        foreach ($args as $arg) {
            if (strlen($arg) > 0) {
                $exec .= ' '.escapeshellarg($arg);
            }
        }

        $output = array();
        $returnCode = null;
        exec("$exec 2>&1", $output, $returnCode);

        if ($returnCode) {
            throw new \Exception(sprintf('Composer command "%s" failed:\n%s"', $exec, implode("\n", $output)));
        }

        return $output;
    }

    /**
     * Check lines for not having any deprecation notice.
     * @param string[] $lines
     */
    protected function assertDeprecationNotice($lines)
    {
        foreach ($lines as $line) {
            $this->assertNotContains('Deprecation Notice:', $line);
        }
    }
}
