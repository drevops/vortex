<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Functional;

use DrevOps\Vortex\Docs\CastNormalizer;
use DrevOps\Vortex\Docs\VideoRecorder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests that recording one session twice produces the same artefacts.
 *
 * This is the claim the documentation demos rest on, and the only way to check
 * it is to record twice and compare. It runs the real pipeline - asciinema,
 * the normalizer and both renderers - against a command chosen for having
 * nothing in its output that could vary.
 */
#[Group('p5')]
class VideoRecordingTest extends TestCase {

  /**
   * Directory holding the recordings a test made.
   */
  protected string $workspace = '';

  /**
   * Path to the documentation utilities.
   */
  protected string $utils = '';

  protected function setUp(): void {
    $root = dirname(__DIR__, 3);
    $this->utils = $root . '/docs/.utils';

    foreach (['asciinema', 'node', 'npx'] as $binary) {
      if (!$this->commandExists($binary)) {
        $this->markTestSkipped(sprintf('"%s" is not installed.', $binary));
      }
    }

    if (!is_dir($root . '/docs/node_modules')) {
      $this->markTestSkipped('Documentation Node dependencies are not installed.');
    }

    $this->workspace = dirname($root) . '/.artifacts/tmp/videos-test-' . getmypid();
    if (!is_dir($this->workspace) && !mkdir($this->workspace, 0o755, TRUE) && !is_dir($this->workspace)) {
      throw new \RuntimeException('Could not create ' . $this->workspace);
    }
  }

  protected function tearDown(): void {
    if ($this->workspace !== '' && is_dir($this->workspace)) {
      (new VideoRecorder(dirname(__DIR__, 4), $this->workspace, $this->utils . '/svg-term-render.js'))->rmrf($this->workspace);
    }
  }

  /**
   * Tests that two recordings of one command produce identical artefacts.
   */
  public function testRecordingTwiceProducesIdenticalArtefacts(): void {
    $root = dirname(__DIR__, 3);
    $recorder = new VideoRecorder(dirname($root), $this->workspace, $this->utils . '/svg-term-render.js');

    // Typed at a prompt and then run, the same way a demo records a command.
    $command = sprintf('php %s %s', escapeshellarg($this->utils . '/type-and-run.php'), escapeshellarg('printf "one\ntwo\nthree\n"'));

    $artefacts = [];
    foreach (['first', 'second'] as $run) {
      $cast = sprintf('%s/%s.json', $this->workspace, $run);

      $recorder->recordSession(cwd: $this->workspace, cast_path: $cast, command: $command, title: 'Recording test', cols: 80, rows: 24);

      // The recorder writes whatever version it was built to write, and the
      // wall clock it ran against reaches the recording. Neither may reach the
      // artefacts.
      $this->assertMatchesRegularExpression('/^\{"version":[23],/', (string) file_get_contents($cast));

      $recorder->normalizeCast($cast, new CastNormalizer(frame_delay: 0.2));
      $recorder->renderSvg($cast, sprintf('%s/%s.svg', $this->workspace, $run));
      $recorder->renderPng($cast, sprintf('%s/%s.png', $this->workspace, $run));

      foreach (['json', 'svg', 'png'] as $extension) {
        $path = sprintf('%s/%s.%s', $this->workspace, $run, $extension);
        $this->assertFileExists($path);
        $artefacts[$extension][$run] = (string) file_get_contents($path);
      }
    }

    foreach ($artefacts as $extension => $contents) {
      $this->assertSame($contents['first'], $contents['second'], sprintf('Two recordings of one session produced different %s output.', $extension));
    }

    // The canonical cast is version 2 whichever version was recorded, carries
    // no recording timestamp, and starts its timeline at zero.
    $canonical = $artefacts['json']['first'];
    $header = json_decode(explode("\n", $canonical)[0], TRUE);
    $this->assertIsArray($header);
    $this->assertSame(2, $header['version']);
    $this->assertArrayNotHasKey('timestamp', $header);
    $this->assertArrayNotHasKey('env', $header);
    $this->assertStringContainsString('[0,"o","$ "]', $canonical);
  }

  /**
   * Whether a binary is on the PATH.
   *
   * @param string $command
   *   The binary name.
   *
   * @return bool
   *   TRUE when it is executable.
   */
  protected function commandExists(string $command): bool {
    $path = getenv('PATH');
    if (!is_string($path) || $path === '') {
      return FALSE;
    }

    foreach (explode(PATH_SEPARATOR, $path) as $directory) {
      if ($directory !== '' && is_file($directory . DIRECTORY_SEPARATOR . $command) && is_executable($directory . DIRECTORY_SEPARATOR . $command)) {
        return TRUE;
      }
    }

    return FALSE;
  }

}
