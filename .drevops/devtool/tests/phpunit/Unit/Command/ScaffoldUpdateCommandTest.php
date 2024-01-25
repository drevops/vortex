<?php

namespace DrevOps\DevTool\Tests\Unit\Command;

use DrevOps\DevTool\Command\ScaffoldUpdateCommand;
use DrevOps\DevTool\Tests\Traits\VfsTrait;
use org\bovigo\vfs\vfsStream;

/**
 * Class SayHelloCommandTest.
 *
 * This is a unit test for the SayHelloCommand class.
 *
 * @coversDefaultClass \DrevOps\DevTool\Command\ScaffoldUpdateCommand
 */
class ScaffoldUpdateCommandTest extends CommandTestCase {

  use VfsTrait;

  /**
   * Path to root directory.
   */
  protected string $rootDir;

  /**
   * Path to scaffold directory.
   */
  protected string $scaffoldDir;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $root_dir_container = $this->vfsCreateDirectory('root_dir');
    $this->rootDir = $root_dir_container->url();

    vfsStream::copyFromFileSystem($this->fixturesDir . '/root_dir', $root_dir_container, 1024 * 10);

    $this->scaffoldDir = $this->vfsCreateDirectory('scaffold_dir')->url();
  }

  /**
   * Test the execute method.
   *
   * @covers ::execute
   * @covers ::doExecute
   * @covers ::initIo
   * @covers ::configure
   * @group command
   */
  public function testExecute(): void {
    $output = $this->runExecute(ScaffoldUpdateCommand::class, [
      '--root' => $this->rootDir,
    ]);
    $this->assertArrayContainsString('Updating composer.json', $output);

    $this->assertArrayContainsString('Finished update scaffold files', $output);
  }

}
