<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Unit;

use DrevOps\Vortex\Docs\CastNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CastNormalizerTest extends TestCase {

  /**
   * Tests that the header keeps only what the session decided.
   */
  public function testHeaderDropsMachineValues(): void {
    $cast = static::cast([[0.0, 'hello']], [
      'timestamp' => 1787182443,
      'env' => ['SHELL' => '/opt/homebrew/opt/bash/bin/bash'],
      'title' => 'Vortex ahoy info Demo',
      'command' => "php '/Users/someone/vortex/type-and-run.php' 'ahoy info'",
    ]);

    $header = static::header((new CastNormalizer())->normalize($cast));

    $this->assertSame([
      'version' => 2,
      'width' => 80,
      'height' => 24,
      'title' => 'Vortex ahoy info Demo',
      'command' => "php '/home/user/vortex/type-and-run.php' 'ahoy info'",
    ], $header);
  }

  /**
   * Tests that a write the read buffer cut in two becomes one frame again.
   */
  #[DataProvider('dataProviderJoinBufferSplits')]
  public function testJoinBufferSplits(int $length, float $gap, int $expected): void {
    $cast = static::cast([
      [1.0, str_repeat('x', $length)],
      [1.0 + $gap, "tail\r\n"],
    ]);

    $this->assertCount($expected, static::payloads((new CastNormalizer(typed: FALSE))->normalize($cast)));
  }

  public static function dataProviderJoinBufferSplits(): \Iterator {
    // A full buffer followed immediately: one write, plus the end pause.
    yield 'full buffer, immediate' => [CastNormalizer::READ_BUFFER, 0.0001, 2];

    yield 'full buffer within tolerance' => [CastNormalizer::READ_BUFFER - CastNormalizer::READ_BUFFER_TOLERANCE, 0.0001, 2];

    // A full buffer followed later is two writes: the session paused.
    yield 'full buffer, delayed' => [CastNormalizer::READ_BUFFER, 0.5, 3];

    // A short chunk was never cut, however fast the next one arrived.
    yield 'short chunk, immediate' => [100, 0.0001, 3];

    yield 'just short of the buffer' => [CastNormalizer::READ_BUFFER - CastNormalizer::READ_BUFFER_TOLERANCE - 1, 0.0001, 3];
  }

  /**
   * Tests that a typed command line drives the delays around it.
   */
  public function testTypedCommandTimeline(): void {
    $cast = static::cast([
      [0.4, '$ '],
      [0.9, 'l'],
      [1.6, 's'],
      [2.9, "\r\n"],
      [7.4, "one\r\n"],
      [9.1, "two\r\n"],
    ]);

    $frames = static::frames((new CastNormalizer(frame_delay: 0.2))->normalize($cast));

    $this->assertSame([
      [0.0, '$ '],
      [0.05, 'l'],
      [0.1, 's'],
      [0.15, "\r\n"],
      [1.15, "one\r\n"],
      [1.35, "two\r\n"],
      [4.35, ''],
    ], $frames);
  }

  /**
   * Tests that a timestamp is written without consulting the machine.
   */
  public function testTimestampFormattingIsIndependentOfSerializePrecision(): void {
    $cast = static::cast([[0.4, 'one'], [0.9, 'two'], [1.4, 'three']]);
    $normalizer = new CastNormalizer(frame_delay: 0.1 + 0.2, typed: FALSE);

    $original = ini_get('serialize_precision');
    ini_set('serialize_precision', '17');

    try {
      $this->assertSame([[0.0, 'one'], [0.3, 'two'], [0.6, 'three'], [3.6, '']], static::frames($normalizer->normalize($cast)));
    }
    finally {
      ini_set('serialize_precision', $original === FALSE ? '-1' : $original);
    }
  }

  /**
   * Tests that output plays at one rate when nothing is typed.
   */
  public function testUntypedTimeline(): void {
    $cast = static::cast([[0.4, "one\r\n"], [8.9, "two\r\n"]]);

    $frames = static::frames((new CastNormalizer(frame_delay: 0.2, typed: FALSE))->normalize($cast));

    $this->assertSame([[0.0, "one\r\n"], [0.2, "two\r\n"], [3.2, '']], $frames);
  }

  /**
   * Tests that a repaint is a frame and the session's pauses survive as steps.
   */
  public function testRepaintFramesAndSteps(): void {
    $cast = static::cast([
      [0.4, "banner\r\n"],
      // Two repaints inside one write, then one that follows a pause.
      [0.5, "\x1b[2A\x1b[Jfirst\x1b[3Asecond"],
      [0.6 + CastNormalizer::MERGE_WINDOW, "\x1b[3Athird"],
    ]);

    $frames = static::frames((new CastNormalizer(mode: CastNormalizer::MODE_REPAINT, frame_delay: 0.2))->normalize($cast));

    $this->assertSame([
      [0.0, "banner\r\n"],
      [0.2, "\x1b[2A\x1b[Jfirst"],
      [0.4, "\x1b[3Asecond"],
      [1.4, "\x1b[3Athird"],
      [4.4, ''],
    ], $frames);
  }

  /**
   * Tests that a frame drawing nothing joins the frame it introduces.
   */
  public function testBlankFramesFoldForward(): void {
    $cast = static::cast([
      [0.1, "\x1b[?25l"],
      [0.2, "\x1b[A"],
      [0.3, "\x1b[A"],
      [0.4, 'drawn'],
      [0.5, "\x1b[?25h"],
    ]);

    $frames = static::frames((new CastNormalizer(typed: FALSE))->normalize($cast));

    $this->assertSame([[0.0, "\x1b[?25l\x1b[A\x1b[Adrawn\x1b[?25h"], [3.0, '']], $frames);
  }

  /**
   * Tests that a progress indicator keeps its first and last state only.
   */
  public function testInPlaceRedrawsCollapse(): void {
    $cast = static::cast([
      [0.1, ' 0/60   0%'],
      [0.2, "\x1b[1G\x1b[2K 20/60  33%"],
      [0.3, "\x1b[1G\x1b[2K 41/60  68%"],
      [0.4, "\x1b[1G\x1b[2K 60/60 100%"],
      [0.5, "\r\ndone\r\n"],
    ]);

    $this->assertSame([
      ' 0/60   0%',
      "\x1b[1G\x1b[2K 60/60 100%",
      "\r\ndone\r\n",
      '',
    ], static::payloads((new CastNormalizer(typed: FALSE))->normalize($cast)));
  }

  /**
   * Tests that a progress indicator hiding the cursor still collapses.
   */
  public function testInPlaceRedrawsCollapseWithCursorHidden(): void {
    $cast = static::cast([
      [0.1, "\x1b[?25l 0/60   0%"],
      [0.2, "\x1b[?25l\x1b[1G\x1b[2K 20/60  33%"],
      [0.3, "\x1b[?25l\x1b[1G\x1b[2K 41/60  68%"],
      [0.4, "\x1b[?25l\x1b[1G\x1b[2K 60/60 100%"],
      [0.5, "\r\ndone\r\n"],
    ]);

    $this->assertSame([
      "\x1b[?25l 0/60   0%",
      "\x1b[?25l\x1b[1G\x1b[2K 60/60 100%",
      "\r\ndone\r\n",
      '',
    ], static::payloads((new CastNormalizer(typed: FALSE))->normalize($cast)));
  }

  /**
   * Tests that a repaint is not mistaken for an in-place redraw.
   */
  public function testRepaintsDoNotCollapse(): void {
    $cast = static::cast([
      [0.1, "\x1b[1G\x1b[2A\x1b[Jfirst"],
      [0.2, "\x1b[1G\x1b[2A\x1b[Jsecond"],
    ]);

    $this->assertCount(3, static::payloads((new CastNormalizer(typed: FALSE))->normalize($cast)));
  }

  /**
   * Tests that the line expect echoes for a spawned process is not drawn.
   */
  public function testSpawnEchoIsDropped(): void {
    $cast = static::cast([
      [0.1, "spawn php installer.php --destination=star_wars\r\n"],
      [0.2, "banner\r\n"],
    ]);

    $this->assertSame(["banner\r\n", ''], static::payloads((new CastNormalizer(typed: FALSE))->normalize($cast)));
  }

  /**
   * Tests that values differing between two runs are masked.
   */
  #[DataProvider('dataProviderMask')]
  public function testMask(string $recorded, string $expected): void {
    $this->assertSame($expected, (new CastNormalizer())->mask($recorded));
  }

  public static function dataProviderMask(): \Iterator {
    yield 'user home' => ['/Users/someone/vortex/.env', '/home/user/vortex/.env'];

    yield 'linux home' => ['Path: /home/someone/.docker/cli-plugins/docker-buildx', 'Path: /home/user/.docker/cli-plugins/docker-buildx'];

    yield 'masked home is left alone' => ['/home/user/demo/star_wars', '/home/user/demo/star_wars'];

    yield 'login link' => [
      'http://site.docker.amazee.io/user/reset/1/1787192699/Cs7-tOken_9/login',
      'http://site.docker.amazee.io/user/reset/1/[TIME]/[REDACTED]/login',
    ];

    yield 'login link already redacted' => [
      'http://site.docker.amazee.io/user/reset/1/1787192699/[REDACTED]/login',
      'http://site.docker.amazee.io/user/reset/1/[TIME]/[REDACTED]/login',
    ];

    yield 'phpunit summary' => ['Time: 10:24.893, Memory: 18.00 MB', 'Time: [TIME], Memory: [MEMORY]'];

    yield 'phpunit coverage timing' => ['done [00:00.018]', 'done [TIME]'];

    yield 'phpcs summary' => ['Time: 1.77 secs; Memory: 16MB', 'Time: [TIME]; Memory: [MEMORY]'];

    yield 'gherkinlint summary' => ['found in 12 files (took 0.0495 seconds)', 'found in 12 files (took [TIME])'];

    yield 'behat summary' => ['0m46.02s (66.71Mb)', '[TIME] ([MEMORY])'];

    yield 'browser output file' => [
      'browser_output/Drupal_Tests_ExampleTest-3-64373793.html',
      'browser_output/Drupal_Tests_ExampleTest-[ID].html',
    ];

    yield 'task duration' => ['[ OK ] Cache was rebuilt. (3s)', '[ OK ] Cache was rebuilt. ([TIME])'];

    yield 'long task duration' => ['[ OK ] Finished site provisioning (1m 2s).', '[ OK ] Finished site provisioning ([TIME]).'];

    yield 'image digest' => [
      'writing image sha256:8a8c78a93a7832368d9390e73639ac1215c4ca4bfa61063d6a74e40f61693a27 done',
      'writing image sha256:[HASH] done',
    ];

    yield 'engine counters' => [" Containers: 351\r\n  Running: 27\r\n Images: 294", " Containers: [COUNT]\r\n  Running: [COUNT]\r\n Images: [COUNT]"];

    yield 'host port' => ['Solr URL on host : http://127.0.0.1:51389', 'Solr URL on host : http://127.0.0.1:[PORT]'];

    yield 'labelled host port' => ["DB port on host             : 51391 ('ahoy db')", "DB port on host             : [PORT] ('ahoy db')"];

    yield 'build step duration' => ['#15 DONE 1.4s', '#15 DONE [TIME]'];

    yield 'compound duration' => ['elapsed 1h2m3.4s here', 'elapsed [TIME] here'];

    yield 'transfer size' => ['#12 transferring context: 1.23kB done', '#12 transferring context: [SIZE] done'];

    yield 'version is not a duration' => ['Drupal core version : 11.4.5', 'Drupal core version : 11.4.5'];

    yield 'test count is not a size' => ['OK (128 tests, 599 assertions)', 'OK (128 tests, 599 assertions)'];
  }

  /**
   * Tests that a credential recorded from the session never reaches a demo.
   */
  #[DataProvider('dataProviderRedactSecrets')]
  public function testRedactSecrets(string $recorded, string $expected): void {
    $this->assertSame($expected, (new CastNormalizer())->mask($recorded));
  }

  public static function dataProviderRedactSecrets(): \Iterator {
    yield 'github token' => ['token=ghp_' . str_repeat('a', 36), 'token=XXXXX'];

    yield 'github fine-grained token' => ['token=github_pat_' . str_repeat('b', 30), 'token=XXXXX'];

    yield 'aws key' => ['AKIA' . str_repeat('C', 16), 'XXXXX'];
  }

  /**
   * Tests that a sensitive environment variable is masked wherever it appears.
   */
  public function testRedactSecretsFromEnvironment(): void {
    $original = getenv('PACKAGE_TOKEN');
    putenv('PACKAGE_TOKEN=s3cr3t-package-token');

    try {
      $this->assertSame('used XXXXX here', (new CastNormalizer())->mask('used s3cr3t-package-token here'));
    }
    finally {
      putenv($original === FALSE ? 'PACKAGE_TOKEN' : 'PACKAGE_TOKEN=' . $original);
    }
  }

  /**
   * Tests that paths given by the caller are replaced before anything else.
   */
  public function testPathsAreReplaced(): void {
    $normalizer = new CastNormalizer(paths: ['/tmp/workspace' => '/home/user/demo', '/src/vortex' => '/home/user/vortex']);

    $this->assertSame('/home/user/demo/star_wars from /home/user/vortex', $normalizer->mask('/tmp/workspace/star_wars from /src/vortex'));
  }

  /**
   * Tests that two recordings of one session normalize to the same bytes.
   */
  public function testTwoRecordingsOfOneSessionMatch(): void {
    $block = str_repeat('x', CastNormalizer::READ_BUFFER);

    $first = static::cast([
      [0.31, '$ '],
      [0.83, 'l'],
      [0.88, 's'],
      [1.29, "\r\n"],
      [3.01, $block],
      [3.0104, "tail\r\n"],
      [4.77, 'Time: 1.77 secs; Memory: 16MB'],
    ]);

    $second = static::cast([
      [0.07, '$ '],
      [0.51, 'l'],
      [0.59, 's'],
      [1.02, "\r\n"],
      [9.44, $block],
      [9.4431, "tail\r\n"],
      [11.06, 'Time: 2.31 secs; Memory: 22MB'],
    ]);

    $normalizer = new CastNormalizer();

    $this->assertSame($normalizer->normalize($first), $normalizer->normalize($second));
  }

  /**
   * Tests that normalizing an already normalized cast changes nothing.
   */
  #[DataProvider('dataProviderIdempotence')]
  public function testIdempotence(string $mode): void {
    $cast = static::cast([
      [0.4, '$ '],
      [0.9, 'l'],
      [1.3, "\r\n"],
      [2.2, "spawned at /Users/someone/demo in 1.4s\r\n"],
      [2.9, str_repeat('y', CastNormalizer::READ_BUFFER)],
      [2.9004, "\x1b[2A\x1b[Jrepainted\r\n"],
      [4.4, ' 0/60   0%'],
      [4.5, "\x1b[1G\x1b[2K 60/60 100%"],
    ]);

    $normalizer = new CastNormalizer(mode: $mode);
    $once = $normalizer->normalize($cast);

    $this->assertSame($once, $normalizer->normalize($once));
  }

  public static function dataProviderIdempotence(): \Iterator {
    yield 'writes' => [CastNormalizer::MODE_WRITES];

    yield 'repaint' => [CastNormalizer::MODE_REPAINT];
  }

  /**
   * Tests that a cast which cannot be normalized is reported rather than used.
   */
  #[DataProvider('dataProviderNormalizeFailure')]
  public function testNormalizeFailure(string $cast, string $message): void {
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage($message);

    (new CastNormalizer())->normalize($cast);
  }

  public static function dataProviderNormalizeFailure(): \Iterator {
    yield 'no events' => ['{"version":2}' . "\n", 'Cast is empty or malformed.'];

    yield 'empty' => ['', 'Cast is empty or malformed.'];

    yield 'header is not json' => ["not json\n" . '[0,"o","x"]', 'Cast header is not valid JSON.'];

    yield 'nothing drawn' => ['{"version":2}' . "\n" . '[0,"x","0"]', 'Cast carries no frames to draw.'];
  }

  /**
   * Tests that an unknown frame mode is refused.
   */
  public function testUnknownModeIsRefused(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Unknown frame mode: lines');

    new CastNormalizer(mode: 'lines');
  }

  /**
   * Build a cast from a list of [time, output] pairs.
   *
   * @param array<int, array{0: float, 1: string}> $events
   *   Recorded output events.
   * @param array<string, mixed> $header
   *   Header values to set on top of the defaults.
   *
   * @return string
   *   Contents of a cast file.
   */
  protected static function cast(array $events, array $header = []): string {
    $header += ['version' => 2, 'width' => 80, 'height' => 24, 'timestamp' => 1787182443, 'title' => 'Demo', 'command' => 'demo'];

    $lines = [json_encode($header, JSON_UNESCAPED_SLASHES)];
    foreach ($events as $event) {
      $lines[] = json_encode([$event[0], 'o', $event[1]], JSON_UNESCAPED_SLASHES);
    }

    return implode("\n", $lines) . "\n";
  }

  /**
   * Return the header of a cast as an array.
   *
   * @param string $cast
   *   Contents of a cast file.
   *
   * @return array<string, mixed>
   *   Decoded header.
   */
  protected static function header(string $cast): array {
    $lines = explode("\n", trim($cast));
    $header = json_decode($lines[0], TRUE);

    return is_array($header) ? $header : [];
  }

  /**
   * Return the [time, output] pairs of a cast.
   *
   * @param string $cast
   *   Contents of a cast file.
   *
   * @return array<int, array{0: float, 1: string}>
   *   Decoded frames.
   */
  protected static function frames(string $cast): array {
    $lines = explode("\n", trim($cast));
    array_shift($lines);

    $frames = [];
    foreach ($lines as $line) {
      $event = json_decode($line, TRUE);
      if (!is_array($event) || !isset($event[0]) || !is_numeric($event[0]) || !isset($event[2]) || !is_string($event[2])) {
        continue;
      }
      $frames[] = [(float) $event[0], $event[2]];
    }

    return $frames;
  }

  /**
   * Return the output each frame of a cast draws.
   *
   * @param string $cast
   *   Contents of a cast file.
   *
   * @return array<int, string>
   *   Frame payloads, in order.
   */
  protected static function payloads(string $cast): array {
    return array_map(static fn(array $frame): string => $frame[1], static::frames($cast));
  }

}
