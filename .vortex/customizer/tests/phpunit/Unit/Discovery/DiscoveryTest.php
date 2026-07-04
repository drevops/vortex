<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Discovery;

use DrevOps\Customizer\Discovery\Discovery;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests config-declared discovery shortcuts.
 */
#[CoversClass(Discovery::class)]
#[Group('discovery')]
final class DiscoveryTest extends TestCase {

  /**
   * The virtual project directory.
   */
  protected string $dir;

  protected function setUp(): void {
    parent::setUp();
    vfsStream::setup('project', NULL, [
      '.env' => "# comment\nDRUPAL_PROFILE=standard\nSITE_NAME=\"Acme Site\"\nEMPTY=\n",
      'composer.json' => '{"name": "acme/site", "extra": {"drupal": {"webroot": "web"}}}',
      'web' => [
        'sites' => ['default' => ['settings.php' => '<?php']],
        'modules' => ['custom' => ['alpha' => ['alpha.info.yml' => ''], 'beta' => ['beta.info.yml' => '']]],
      ],
    ]);
    $this->dir = vfsStream::url('project');
  }

  public function testDotenv(): void {
    $discovery = new Discovery();

    $this->assertSame('standard', $discovery->detect(['dotenv' => 'DRUPAL_PROFILE'], $this->dir));
    $this->assertSame('Acme Site', $discovery->detect(['dotenv' => 'SITE_NAME'], $this->dir));
    $this->assertSame('', $discovery->detect(['dotenv' => 'EMPTY'], $this->dir));
    $this->assertNull($discovery->detect(['dotenv' => 'MISSING'], $this->dir));
  }

  public function testJson(): void {
    $discovery = new Discovery();

    $this->assertSame('acme/site', $discovery->detect(['json' => ['file' => 'composer.json', 'path' => 'name']], $this->dir));
    $this->assertSame('web', $discovery->detect(['json' => ['file' => 'composer.json', 'path' => 'extra.drupal.webroot']], $this->dir));
    $this->assertNull($discovery->detect(['json' => ['file' => 'composer.json', 'path' => 'nope.deep']], $this->dir));
    $this->assertNull($discovery->detect(['json' => ['file' => 'missing.json', 'path' => 'name']], $this->dir));
  }

  public function testExists(): void {
    $discovery = new Discovery();

    $this->assertTrue($discovery->detect(['exists' => 'web/sites/default/settings.php'], $this->dir));
    $this->assertFalse($discovery->detect(['exists' => 'web/nope.php'], $this->dir));
  }

  public function testScan(): void {
    $discovery = new Discovery();

    $this->assertSame(['alpha', 'beta'], $discovery->detect(['scan' => ['dir' => 'web/modules/custom', 'type' => 'dir']], $this->dir));
    $this->assertSame([], $discovery->detect(['scan' => ['dir' => 'web/nope']], $this->dir));
  }

  public function testUnknownRuleReturnsNull(): void {
    $this->assertNull((new Discovery())->detect(['bogus' => 'x'], $this->dir));
  }

  public function testCleanDirectoryDiscoversNothing(): void {
    vfsStream::setup('empty');
    $dir = vfsStream::url('empty');
    $discovery = new Discovery();

    $this->assertNull($discovery->detect(['dotenv' => 'DRUPAL_PROFILE'], $dir));
    $this->assertNull($discovery->detect(['json' => ['file' => 'composer.json', 'path' => 'name']], $dir));
    $this->assertFalse($discovery->detect(['exists' => 'web'], $dir));
    $this->assertSame([], $discovery->detect(['scan' => ['dir' => 'web']], $dir));
  }

}
