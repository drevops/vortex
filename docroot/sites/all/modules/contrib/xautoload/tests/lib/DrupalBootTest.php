<?php


namespace Drupal\xautoload\Tests;


use Drupal\xautoload\DrupalSystem\MockDrupalSystem;
use Drupal\xautoload\Tests\Filesystem\StreamWrapper;
use Drupal\xautoload\Tests\Filesystem\VirtualFilesystem;

class DrupalBootTest extends \PHPUnit_Framework_TestCase {

  /**
   * @var VirtualFilesystem
   */
  protected $filesystem;

  function setUp() {
    parent::setUp();
    $this->filesystem = StreamWrapper::register('test');
  }

  function tearDown() {
    stream_wrapper_unregister('test');
    parent::tearDown();
  }

  /**
   * Tests a simulated regular request.
   */
  function testNormalRequest() {

    // Create virtual class files.
    $this->filesystem->addClass(
      'test://modules/testmod_psr0/lib/Drupal/testmod_psr0/Foo.php',
      'Drupal\testmod_psr0\Foo');
    $this->filesystem->addClass(
      'test://modules/testmod_psr4/lib/Foo.php',
      'Drupal\testmod_psr4\Foo');
    $this->filesystem->addClass(
      'test://modules/testmod_pearflat/lib/Foo.php',
      'testmod_pearflat_Foo');

    $this->assertTrue(
      file_exists('test://modules/testmod_psr0/lib/Drupal/testmod_psr0/Foo.php'),
      'Stream wrapper file exists.');

    $services = xautoload()->getServiceContainer();

    // Mock out DrupalSystem in the service container.
    $extensions = $this->getExampleExtensions();
    $system = new MockDrupalSystem(array(), $extensions);
    $services->set('system', $system);

    // Simulate _xautoload_register_drupal().

    // No cache is active.
    // Initialize the finder, to fire scheduled operations.
    $services->proxyFinder->getFinder();

    // Register prefixes and namespaces for enabled extensions.
    $operation = new FinderOperation\BootPhase($extensions);
    $services->proxyFinder->onFinderInit($operation);

    // Simulate inclusion of other module files.
    // The testmod_psr4.module must contain an equivalent to the following line,
    // to tell xautoload that PSR-4 is in action:
    $services->main->registerModulePsr4('test://modules/testmod_psr4/testmod_psr4.module', 'lib');

    // Boot modules use their classes.
    $this->assertLoadClass('Drupal\testmod_psr0\Foo');
    $this->assertLoadClass('Drupal\testmod_psr4\Foo');
    $this->assertLoadClass('testmod_pearflat_Foo');
  }

  /**
   * @return \stdClass[]
   */
  protected function getExampleExtensions() {
    return array_fill_keys(array(
      'system', 'views', 'menu_block',
      'testmod_psr0', 'testmod_psr4', 'testmod_pearflat'
    ), 'module');
  }

  /**
   * @param string $class
   */
  protected function assertLoadClass($class) {
    $this->assertFalse(class_exists($class, FALSE), "Class '$class' is not defined yet.");
    $this->assertTrue(class_exists($class), "Class '$class' successfully loaded.");
  }
} 