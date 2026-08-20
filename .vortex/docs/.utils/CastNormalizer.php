<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Docs;

/**
 * Rewrites a recorded asciicast onto a canonical timeline.
 *
 * A recording carries whatever chunks the pseudo-terminal happened to deliver,
 * at whatever moment the scheduler delivered them, so two runs of the same
 * session produce different frames and different durations even when nothing
 * about the session changed. The recording is therefore reduced to the writes
 * the session made, cut into frames at boundaries the output itself carries,
 * and replayed at delays chosen here rather than measured on the machine.
 *
 * Output that differs between two runs of the same command - host ports, run
 * durations, image digests, generated file names - is masked, and a run of
 * in-place redraws is collapsed to its final state, so neither the text nor
 * the number of frames depends on the run.
 */
final class CastNormalizer {

  /**
   * Frames follow the writes the session made. Suits streaming command output.
   */
  public const MODE_WRITES = 'writes';

  /**
   * Frames follow the repaints the session made. Suits a full-screen prompt.
   */
  public const MODE_REPAINT = 'repaint';

  /**
   * Bytes the recorder reads from the pseudo-terminal at once.
   */
  public const READ_BUFFER = 1024;

  /**
   * Bytes a full read can fall short by when it ends on a character boundary.
   */
  public const READ_BUFFER_TOLERANCE = 4;

  /**
   * Seconds within which the remainder of a split write arrives.
   */
  public const SPLIT_WINDOW = 0.005;

  /**
   * Seconds each frame plays for, unless the caller chooses otherwise.
   */
  public const FRAME_DELAY = 0.15;

  /**
   * Seconds each typed character plays for.
   */
  public const TYPE_DELAY = 0.05;

  /**
   * Seconds between one step and the next, long enough to read the last one.
   */
  public const STEP_DELAY = 1.0;

  /**
   * Seconds a gap has to reach in repaint mode to begin a new step.
   */
  public const MERGE_WINDOW = 0.35;

  /**
   * Seconds the final frame holds for before the animation loops.
   */
  public const END_PAUSE = 3.0;

  /**
   * Start of a repaint: the cursor-up sequence a full-screen prompt emits.
   */
  public const REPAINT_BOUNDARY = '/(?=\x1b\[[0-9]*A)/';

  /**
   * The line expect echoes for the process it spawned.
   *
   * The echo is not session output. It is recognised by its content, so
   * normalizing an already normalized cast drops nothing further.
   */
  public const SPAWN_ECHO = '/^\s*spawn\s/';

  /**
   * Values that differ between two runs of the same command.
   *
   * Ordered from the most specific pattern to the most general, since each is
   * applied to the result of the one before it.
   */
  public const MASKS = [
    // Home directory of whoever recorded the session.
    '#/Users/[^/\s"\']+/#' => '/home/user/',
    // Drupal one-time login link, which carries an issue time and a token.
    '#/user/reset/(\d+)/\d+/[A-Za-z0-9_\[\]-]+/login#' => '/user/reset/$1/[TIME]/[REDACTED]/login',
    // PHPUnit run summary.
    '#Time: \d+:\d{2}\.\d+, Memory: [\d.]+ ?[KMGT]?i?B#' => 'Time: [TIME], Memory: [MEMORY]',
    // PHPUnit coverage report timing.
    '#\[\d{2}:\d{2}\.\d+\]#' => '[TIME]',
    // PHP_CodeSniffer run summary.
    '#Time: [\d.]+ ?(?:secs?|mins?|hours?); Memory: [\d.]+ ?[KMGT]?i?B#' => 'Time: [TIME]; Memory: [MEMORY]',
    // Gherkin linter run summary.
    '#took [\d.]+ seconds#' => 'took [TIME]',
    // Drupal functional test browser output file name.
    '#(browser_output/[A-Za-z0-9_]+)-\d+-\d+\.html#' => '$1-[ID].html',
    // Provisioning task duration.
    '#\((?:\d+h )?(?:\d+m )?\d+s\)#' => '([TIME])',
    // Container image digest.
    '#sha256:[0-9a-f]{12,64}#' => 'sha256:[HASH]',
    // Docker engine object counters, which include the recording stack itself.
    '#^(\h*(?:Containers|Running|Paused|Stopped|Images):\h+)\d+#m' => '$1[COUNT]',
    // Host port published by the Docker stack.
    '#\b(127\.0\.0\.1|localhost):\d{2,5}#' => '$1:[PORT]',
    '#(port on host\h+: )\d+#' => '$1[PORT]',
    // Build step duration.
    '#\b\d+(?:\.\d+)?s(?=\D|$)#' => '[TIME]',
    // Byte size reported by a build or a transfer.
    '#\b\d+(?:\.\d+)?\h?[kKMGT]?i?B\b#' => '[SIZE]',
  ];

  /**
   * Credential formats that must never reach a published demo.
   */
  public const SECRET_PATTERNS = [
    // GitHub personal access, OAuth, user-to-server, server-to-server and
    // refresh tokens (for example 'ghp_...', 'gho_...').
    '#\bgh[oprsu]_[A-Za-z0-9]{36,255}#',
    // GitHub fine-grained personal access tokens ('github_pat_...').
    '#\bgithub_pat_[A-Za-z0-9_]{22,255}#',
    // AWS access key identifiers.
    '#\b(?:AKIA|ASIA)[A-Z0-9]{16}#',
  ];

  /**
   * Environment variables whose value is masked wherever it appears.
   */
  public const SECRET_VARIABLES = ['PACKAGE_TOKEN', 'GITHUB_TOKEN', 'VORTEX_CONTAINER_REGISTRY_PASS'];

  /**
   * Placeholder that replaces a credential.
   */
  public const SECRET_PLACEHOLDER = 'XXXXX';

  /**
   * Shortest environment variable value that is worth masking.
   */
  public const SECRET_MIN_LENGTH = 8;

  /**
   * How frames are cut from the stream.
   */
  protected string $mode;

  /**
   * Seconds a frame of command output plays for.
   */
  protected float $frameDelay;

  /**
   * Whether the session opens with a command typed at a prompt.
   */
  protected bool $typed;

  /**
   * Absolute paths to replace, keyed by the path, before anything else.
   *
   * @var array<string, string>
   */
  protected array $paths;

  /**
   * Constructs a normalizer for one video.
   *
   * @param string $mode
   *   One of the MODE_* constants.
   * @param float|null $frame_delay
   *   Seconds a frame of command output plays for. NULL uses FRAME_DELAY.
   * @param bool $typed
   *   Whether the session opens with a command typed at a prompt. The typed
   *   characters play at TYPE_DELAY and the command line is followed by a
   *   STEP_DELAY.
   * @param array<string, string> $paths
   *   Absolute paths to replace, keyed by the path.
   */
  public function __construct(string $mode = self::MODE_WRITES, ?float $frame_delay = NULL, bool $typed = TRUE, array $paths = []) {
    if (!in_array($mode, [self::MODE_WRITES, self::MODE_REPAINT], TRUE)) {
      throw new \InvalidArgumentException('Unknown frame mode: ' . $mode);
    }

    $this->mode = $mode;
    $this->frameDelay = $frame_delay ?? self::FRAME_DELAY;
    $this->typed = $typed;
    $this->paths = $paths;
  }

  /**
   * Rewrite a cast onto the canonical timeline.
   *
   * @param string $cast
   *   Contents of an asciicast v2 file.
   *
   * @return string
   *   Contents of the canonical cast, newline-terminated.
   */
  public function normalize(string $cast): string {
    $lines = preg_split('/\R/', $cast);
    $lines = $lines === FALSE ? [] : array_values(array_filter($lines, static fn(string $line): bool => trim($line) !== ''));

    if (count($lines) < 2) {
      throw new \RuntimeException('Cast is empty or malformed.');
    }

    $header = $this->normalizeHeader((string) array_shift($lines));
    $frames = $this->collapseRedraws($this->foldBlankFrames($this->cutFrames($this->readWrites($lines))));

    if ($frames === []) {
      throw new \RuntimeException('Cast carries no frames to draw.');
    }

    return implode("\n", array_merge([$header], $this->buildTimeline($frames))) . "\n";
  }

  /**
   * Mask everything that differs between two runs of the same session.
   *
   * @param string $text
   *   Text recorded from the session.
   *
   * @return string
   *   The same text with volatile values replaced by placeholders.
   */
  public function mask(string $text): string {
    if ($this->paths !== []) {
      $text = str_replace(array_keys($this->paths), array_values($this->paths), $text);
    }

    foreach (self::MASKS as $pattern => $replacement) {
      $masked = preg_replace($pattern, $replacement, $text);
      if ($masked === NULL) {
        throw new \RuntimeException('Failed to apply mask: ' . $pattern);
      }
      $text = $masked;
    }

    return $this->redactSecrets($text);
  }

  /**
   * Replace recognised credentials with a fixed placeholder.
   *
   * Recorded output can surface a real secret - a token replayed by a cached
   * container build layer, or one printed by a provisioning step - and a
   * published demo must never embed one.
   *
   * @param string $text
   *   Text recorded from the session.
   *
   * @return string
   *   The same text with credentials replaced.
   */
  protected function redactSecrets(string $text): string {
    $masked = preg_replace(self::SECRET_PATTERNS, self::SECRET_PLACEHOLDER, $text);
    if ($masked === NULL) {
      throw new \RuntimeException('Failed to mask secret token patterns.');
    }

    // Defence in depth: mask the literal value of a sensitive environment
    // variable when it is set, catching tokens whose format the patterns above
    // do not recognise. A short value is skipped to avoid mangling ordinary
    // text.
    foreach (self::SECRET_VARIABLES as $name) {
      $value = getenv($name);
      if (is_string($value) && strlen($value) >= self::SECRET_MIN_LENGTH) {
        $masked = str_replace($value, self::SECRET_PLACEHOLDER, $masked);
      }
    }

    return $masked;
  }

  /**
   * Rewrite the header, dropping everything the machine contributed.
   *
   * @param string $line
   *   The recorded header line.
   *
   * @return string
   *   The canonical header line.
   */
  protected function normalizeHeader(string $line): string {
    $header = json_decode($line, TRUE);
    if (!is_array($header)) {
      throw new \RuntimeException('Cast header is not valid JSON.');
    }

    $width = $header['width'] ?? 80;
    $height = $header['height'] ?? 24;

    $canonical = [
      'version' => 2,
      'width' => is_numeric($width) ? (int) $width : 80,
      'height' => is_numeric($height) ? (int) $height : 24,
    ];

    foreach (['title', 'command'] as $key) {
      if (isset($header[$key]) && is_string($header[$key])) {
        $canonical[$key] = $this->mask($header[$key]);
      }
    }

    return (string) json_encode($canonical, JSON_UNESCAPED_SLASHES);
  }

  /**
   * Reduce the recorded events to the writes the session made.
   *
   * @param array<int, string> $lines
   *   Recorded event lines.
   *
   * @return list<array{at: float, data: string}>
   *   One entry per write, in order.
   */
  protected function readWrites(array $lines): array {
    $chunks = [];

    foreach ($lines as $line) {
      $event = json_decode(trim($line), TRUE);
      if (!is_array($event) || count($event) < 3 || !is_numeric($event[0]) || ($event[1] ?? '') !== 'o' || !is_string($event[2])) {
        continue;
      }
      $chunks[] = ['at' => (float) $event[0], 'data' => $event[2]];
    }

    $writes = [];
    foreach ($this->joinBufferSplits($chunks) as $write) {
      if ($writes === [] && preg_match(self::SPAWN_ECHO, $this->strip($write['data'])) === 1) {
        continue;
      }
      $writes[] = ['at' => $write['at'], 'data' => $this->mask($write['data'])];
    }

    return $writes;
  }

  /**
   * Rejoin the chunks a full read buffer cut a single write into.
   *
   * A write longer than the read buffer reaches the recording as several
   * chunks, and where the buffer fell is a property of the machine rather than
   * of the session. Such a chunk fills the buffer and its remainder follows
   * within microseconds, so both conditions are required before two chunks are
   * treated as one write.
   *
   * @param list<array{at: float, data: string}> $chunks
   *   Recorded chunks, in order.
   *
   * @return list<array{at: float, data: string}>
   *   One entry per write, in order.
   */
  protected function joinBufferSplits(array $chunks): array {
    $writes = [];
    $open = NULL;
    $previous_length = 0;
    $previous_at = 0.0;

    foreach ($chunks as $chunk) {
      $continues = $previous_length >= self::READ_BUFFER - self::READ_BUFFER_TOLERANCE
        && $chunk['at'] - $previous_at < self::SPLIT_WINDOW;

      if ($open === NULL) {
        $open = $chunk;
      }
      elseif ($continues) {
        $open = ['at' => $open['at'], 'data' => $open['data'] . $chunk['data']];
      }
      else {
        $writes[] = $open;
        $open = $chunk;
      }

      $previous_length = strlen($chunk['data']);
      $previous_at = $chunk['at'];
    }

    if ($open !== NULL) {
      $writes[] = $open;
    }

    return $writes;
  }

  /**
   * Cut the session's output into the frames it is made of.
   *
   * In writes mode a write is a frame: a command flushes each state it has to
   * show. In repaint mode the writes are treated as one stream and cut where
   * the session repainted, because a prompt redraws its whole screen and a
   * half-applied repaint is not a state the screen was ever in.
   *
   * @param list<array{at: float, data: string}> $writes
   *   Writes the session made, in order.
   *
   * @return list<array{at: float, data: string}>
   *   One entry per frame, in order.
   */
  protected function cutFrames(array $writes): array {
    if ($this->mode !== self::MODE_REPAINT) {
      return $writes;
    }

    $stream = '';
    $arrivals = [];
    foreach ($writes as $write) {
      $arrivals[strlen($stream)] = $write['at'];
      $stream .= $write['data'];
    }

    $pieces = preg_split(self::REPAINT_BOUNDARY, $stream, -1, PREG_SPLIT_NO_EMPTY);
    if ($pieces === FALSE) {
      $pieces = [$stream];
    }

    $frames = [];
    $offset = 0;
    foreach ($pieces as $piece) {
      $frames[] = ['at' => $this->arrivalAt($arrivals, $offset), 'data' => $piece];
      $offset += strlen($piece);
    }

    return $frames;
  }

  /**
   * Return the time the write holding an offset arrived.
   *
   * @param array<int, float> $arrivals
   *   Arrival times, keyed by the offset each write starts at.
   * @param int $offset
   *   Offset into the stream.
   *
   * @return float
   *   Seconds from the start of the recording.
   */
  protected function arrivalAt(array $arrivals, int $offset): float {
    $at = 0.0;

    foreach ($arrivals as $start => $time) {
      if ($start > $offset) {
        break;
      }
      $at = $time;
    }

    return $at;
  }

  /**
   * Fold the frames that paint nothing into the frame that follows them.
   *
   * A session moves and hides the cursor before it draws, and a prompt that
   * repaints five lines may do so with five separate cursor-up sequences.
   * Neither changes the screen, so both belong to the frame they introduce.
   *
   * @param list<array{at: float, data: string}> $frames
   *   Frames, in order.
   *
   * @return list<array{at: float, data: string}>
   *   The surviving frames, in order.
   */
  protected function foldBlankFrames(array $frames): array {
    $folded = [];
    $pending = '';
    $pending_at = NULL;

    foreach ($frames as $frame) {
      if ($this->isBlank($frame['data'])) {
        $pending .= $frame['data'];
        $pending_at ??= $frame['at'];
        continue;
      }

      $folded[] = ['at' => $pending_at ?? $frame['at'], 'data' => $pending . $frame['data']];
      $pending = '';
      $pending_at = NULL;
    }

    if ($pending === '' || $folded === []) {
      return $folded;
    }

    $last = array_pop($folded);
    $folded[] = ['at' => $last['at'], 'data' => $last['data'] . $pending];

    return $folded;
  }

  /**
   * Whether a frame moves the cursor around without drawing anything.
   *
   * @param string $data
   *   The frame's output.
   *
   * @return bool
   *   TRUE when nothing is left once the escape sequences are removed.
   */
  protected function isBlank(string $data): bool {
    return $this->strip($data) === '';
  }

  /**
   * Remove the escape sequences from output, leaving what it draws.
   *
   * @param string $data
   *   Output recorded from the session.
   *
   * @return string
   *   The same output without its escape sequences.
   */
  protected function strip(string $data): string {
    return (string) preg_replace('/\x1b\[[0-9;?]*[A-Za-z]/', '', $data);
  }

  /**
   * Drop the frames an in-place redraw immediately overwrites.
   *
   * A progress indicator rewrites its own line as often as the work reports
   * back, so both what those frames hold and how many of them there are depend
   * on the run. Only the last of a run of them survives, alongside the initial
   * draw that precedes it.
   *
   * @param list<array{at: float, data: string}> $frames
   *   Frames, in order.
   *
   * @return list<array{at: float, data: string}>
   *   The surviving frames, in order.
   */
  protected function collapseRedraws(array $frames): array {
    $kept = [];

    foreach ($frames as $index => $frame) {
      $next = $frames[$index + 1] ?? NULL;
      if ($next !== NULL && $this->isLineRedraw($frame) && $this->isLineRedraw($next)) {
        continue;
      }
      $kept[] = $frame;
    }

    return $kept;
  }

  /**
   * Whether a frame redraws the line it is on and nothing else.
   *
   * @param array{at: float, data: string} $frame
   *   The frame to inspect.
   *
   * @return bool
   *   TRUE when the frame returns to the start of the current line and stays
   *   on it.
   */
  protected function isLineRedraw(array $frame): bool {
    if (str_contains($frame['data'], "\n") || preg_match('/\x1b\[[0-9]*[AJ]/', $frame['data']) === 1) {
      return FALSE;
    }

    return preg_match('/^(?:\r(?!\n)|\x1b\[[0-9]*[GDK])/', $frame['data']) === 1;
  }

  /**
   * Place the frames on the canonical timeline.
   *
   * @param list<array{at: float, data: string}> $frames
   *   Frames, in order.
   *
   * @return list<string>
   *   Encoded event lines, in order.
   */
  protected function buildTimeline(array $frames): array {
    $events = [];
    $time = 0.0;
    $typing = $this->typed && $this->mode === self::MODE_WRITES;

    foreach ($frames as $index => $frame) {
      if ($index > 0) {
        $previous = $frames[$index - 1];
        $time += $this->delay($previous, $frame, $typing);
        $typing = $typing && !str_contains($previous['data'], "\n");
      }

      $events[] = (string) json_encode([round($time, 6), 'o', $frame['data']], JSON_UNESCAPED_SLASHES);
    }

    $events[] = (string) json_encode([round($time + self::END_PAUSE, 6), 'o', ''], JSON_UNESCAPED_SLASHES);

    return $events;
  }

  /**
   * Return how long a frame plays for before the next one replaces it.
   *
   * @param array{at: float, data: string} $previous
   *   The frame being played.
   * @param array{at: float, data: string} $current
   *   The frame that replaces it.
   * @param bool $typing
   *   Whether the command line is still being typed.
   *
   * @return float
   *   Seconds.
   */
  protected function delay(array $previous, array $current, bool $typing): float {
    if ($this->mode === self::MODE_REPAINT) {
      // A prompt paces itself, so the recorded gaps are read here, but only as
      // a two-way choice: a repaint that follows within the merge window
      // continues a step, and anything slower begins one.
      return $current['at'] - $previous['at'] < self::MERGE_WINDOW ? $this->frameDelay : self::STEP_DELAY;
    }

    if ($typing) {
      return str_contains($previous['data'], "\n") ? self::STEP_DELAY : self::TYPE_DELAY;
    }

    return $this->frameDelay;
  }

}
