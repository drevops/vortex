<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Traits;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use phpmock\phpunit\PHPMock;

trait MockTrait {

  use PHPMock;

  /**
   * Stores the passthru mock object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|null
   */
  protected $mockPassthru;

  /**
   * Stores passthru responses for the mock.
   *
   * @var array<int, array{cmd:string, output?: string, result_code?: int, return?: NULL|FALSE}>
   */
  protected array $mockPassthruResponses = [];

  /**
   * Current index for passthru responses.
   */
  protected int $mockPassthruIndex = 0;

  /**
   * Flag to track if passthru mocks were already checked.
   */
  protected bool $mockPassthruChecked = FALSE;

  /**
   * Stores request mock objects.
   *
   * @var array<string, \PHPUnit\Framework\MockObject\MockObject>
   */
  protected array $mockRequest = [];

  /**
   * Stores request responses for the mock.
   *
   * @var array<int, array{url: string, method?: string, response: array{ok: bool, status: int, body: string|false, error: string|null, info: array<string, mixed>}}>
   */
  protected array $mockRequestResponses = [];

  /**
   * Current index for request responses.
   */
  protected int $mockRequestIndex = 0;

  /**
   * Flag to track if request mocks were already checked.
   */
  protected bool $mockRequestChecked = FALSE;

  /**
   * Stores the mail mock object.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|null
   */
  protected $mockMail;

  /**
   * Stores mail responses for the mock.
   *
   * @var array<int, array{to: string, subject: string, message: string, result: bool}>
   */
  protected array $mockMailResponses = [];

  /**
   * Current index for mail responses.
   */
  protected int $mockMailIndex = 0;

  /**
   * Flag to track if mail mocks were already checked.
   */
  protected bool $mockMailChecked = FALSE;

  protected function mockTearDown(): void {
    // Verify all mocked passthru responses were consumed.
    $this->mockPassthruAssertAllMocksConsumed();

    // Reset passthru mock.
    $this->mockPassthru = NULL;
    $this->mockPassthruResponses = [];
    $this->mockPassthruIndex = 0;
    $this->mockPassthruChecked = FALSE;

    // Verify all mocked mail responses were consumed.
    $this->mockMailAssertAllMocksConsumed();

    // Reset mail mock.
    $this->mockMail = NULL;
    $this->mockMailResponses = [];
    $this->mockMailIndex = 0;
    $this->mockMailChecked = FALSE;

    // Verify all mocked request responses were consumed.
    $this->mockRequestAssertAllMocksConsumed();

    // Reset request mock.
    $this->mockRequest = [];
    $this->mockRequestResponses = [];
    $this->mockRequestIndex = 0;
    $this->mockRequestChecked = FALSE;
  }

  /**
   * Mock passthru function to return predefined output and exit codes.
   *
   * @param array<int, array{cmd:string, output?: string, result_code?: int, return?: NULL|FALSE}> $responses
   *   Array of responses to return for each passthru call.
   *   Each response should have:
   *   - output: The output to echo
   *   - exit_code: The exit code to set (0 for success).
   * @param string $namespace
   *   Namespace to mock the functions in (defaults to DrevOps\VortexTooling).
   *
   * @throws \RuntimeException
   *   When more passthru calls are made than mocked responses available.
   */
  protected function mockPassthruMultiple(array $responses, string $namespace = 'DrevOps\\VortexTooling'): void {
    // Add responses to the class property.
    $this->mockPassthruResponses = array_merge($this->mockPassthruResponses, $responses);

    // If mock already exists, just add to responses and return.
    if ($this->mockPassthru !== NULL) {
      return;
    }

    // Create and store the mock.
    $this->mockPassthru = $this->getFunctionMock($namespace, 'passthru');
    $this->mockPassthru
      ->expects($this->any())
      ->willReturnCallback(function ($command, &...$args): null|false {
        $total_responses = count($this->mockPassthruResponses);

        if ($this->mockPassthruIndex >= $total_responses) {
          throw new \RuntimeException(sprintf('passthru() called more times than mocked responses. Expected %d call(s), but attempting call #%d.', $total_responses, $this->mockPassthruIndex + 1));
        }

        $response = $this->mockPassthruResponses[$this->mockPassthruIndex++];

        $response += [
          'output' => '',
          'result_code' => 0,
          'return' => NULL,
        ];

        // Validate response structure.
        // @phpstan-ignore-next-line isset.offset
        if (!isset($response['cmd'])) {
          throw new \InvalidArgumentException('Mocked passthru response must include "cmd" key to specify expected command.');
        }

        // @phpstan-ignore-next-line booleanAnd.alwaysFalse
        if ($response['return'] !== FALSE && $response['return'] !== NULL) {
          throw new \InvalidArgumentException(sprintf('Mocked passthru response "return" key must be either NULL or FALSE, but got %s.', gettype($response['return'])));
        }

        // Expectation error.
        if ($response['cmd'] !== $command) {
          throw new \RuntimeException(sprintf('passthru() called with unexpected command. Expected "%s", got "%s".', $response['cmd'], $command));
        }

        echo $response['output'];

        // Set exit code only if it was passed by reference.
        // Using spread operator to distinguish between no argument and NULL.
        if (count($args) > 0) {
          $args[0] = $response['result_code'];
        }

        return $response['return'];
      });
  }

  /**
   * Mock single passthru call.
   *
   * @param array{cmd:string, output?: string, result_code?: int, return?: NULL|FALSE} $response
   *   Response with output and exit_code.
   * @param string $namespace
   *   Namespace to mock the functions in.
   */
  protected function mockPassthru(array $response, string $namespace = 'DrevOps\\VortexTooling'): void {
    $this->mockPassthruMultiple([$response], $namespace);
  }

  /**
   * Verify all mocked passthru responses were consumed.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all mocked responses were consumed.
   */
  protected function mockPassthruAssertAllMocksConsumed(): void {
    if ($this->mockPassthru !== NULL && !$this->mockPassthruChecked) {
      $this->mockPassthruChecked = TRUE;

      $total_responses = count($this->mockPassthruResponses);
      $consumed_responses = $this->mockPassthruIndex;

      if ($consumed_responses < $total_responses) {
        $this->fail(sprintf('Not all mocked passthru responses were consumed. Expected %d call(s), but only %d call(s) were made.', $total_responses, $consumed_responses));
      }
    }
  }

  /**
   * Mock quit() function to throw QuitErrorException instead of terminating.
   *
   * This allows testing code that calls quit() without actually exiting.
   * The mock will throw an QuitErrorException with the exit code, which can be
   * caught and asserted in tests.
   *
   * @param int $code
   *   Exit code to expect (0 for success, non-zero for error).
   * @param string $namespace
   *   Namespace to mock the function in (defaults to DrevOps\VortexTooling).
   */
  protected function mockQuit(int $code = 0, string $namespace = 'DrevOps\\VortexTooling'): void {
    $quit = $this->getFunctionMock($namespace, 'quit');
    $quit
      ->expects($this->any())
      ->willReturnCallback(function (int $exit_code = 0) use ($code): void {
        // Expectation error.
        if ($code !== $exit_code) {
          throw new \RuntimeException(sprintf('quit() called with unexpected exit code. Expected %d, got %d.', $code, $exit_code));
        }
        // Non-zero exit code throws QuitErrorException to simulate exit.
        if ($code !== 0) {
          throw new QuitErrorException($code);
        }

        throw new QuitSuccessException($code);
      });
  }

  /**
   * Mock mail() function to return predefined results.
   *
   * @param array<int, array{to: string, subject: string, message: string, result?: bool}> $responses
   *   Array of responses to return for each mail call.
   *   Each response should have:
   *   - to: Expected recipient (required)
   *   - subject: Expected subject (required)
   *   - message: Expected message (required)
   *   - result: Return value (TRUE for success, FALSE for failure, default: TRUE).
   * @param string $namespace
   *   Namespace to mock the function in (defaults to DrevOps\VortexTooling).
   *
   * @throws \RuntimeException
   *   When more mail calls are made than mocked responses available.
   */
  protected function mockMailMultiple(array $responses, string $namespace = 'DrevOps\\VortexTooling'): void {
    // Normalize responses by applying defaults before storing.
    $normalized_responses = [];
    foreach ($responses as $response) {
      $response += [
        'result' => TRUE,
      ];
      $normalized_responses[] = $response;
    }

    // Add normalized responses to the class property.
    $this->mockMailResponses = array_merge($this->mockMailResponses, $normalized_responses);

    // If mock already exists, just add to responses and return.
    if ($this->mockMail !== NULL) {
      return;
    }

    // Create and store the mock.
    $this->mockMail = $this->getFunctionMock($namespace, 'mail');
    $this->mockMail
      ->expects($this->any())
      ->willReturnCallback(function (string $to, string $subject, string $message, array|string $additional_headers = [], string $additional_params = ''): bool {
        $total_responses = count($this->mockMailResponses);

        if ($this->mockMailIndex >= $total_responses) {
          throw new \RuntimeException(sprintf('mail() called more times than mocked responses. Expected %d call(s), but attempting call #%d.', $total_responses, $this->mockMailIndex + 1));
        }

        $response = $this->mockMailResponses[$this->mockMailIndex++];

        // Validate response structure.
        // @phpstan-ignore-next-line isset.offset
        if (!isset($response['to'])) {
          throw new \InvalidArgumentException('Mocked mail response must include "to" key to specify expected recipient.');
        }
        // @phpstan-ignore-next-line isset.offset
        if (!isset($response['subject'])) {
          throw new \InvalidArgumentException('Mocked mail response must include "subject" key to specify expected subject.');
        }
        // @phpstan-ignore-next-line isset.offset
        if (!isset($response['message'])) {
          throw new \InvalidArgumentException('Mocked mail response must include "message" key to specify expected message.');
        }

        // Expectation errors.
        if ($response['to'] !== $to) {
          throw new \RuntimeException(sprintf('mail() called with unexpected recipient. Expected "%s", got "%s".', $response['to'], $to));
        }

        if ($response['subject'] !== $subject) {
          throw new \RuntimeException(sprintf('mail() called with unexpected subject. Expected "%s", got "%s".', $response['subject'], $subject));
        }

        if ($response['message'] !== $message) {
          throw new \RuntimeException(sprintf('mail() called with unexpected message. Expected "%s", got "%s".', $response['message'], $message));
        }

        return $response['result'];
      });
  }

  /**
   * Mock single mail call.
   *
   * @param array{to: string, subject: string, message: string, result?: bool} $response
   *   Response with recipient, subject, message, and result.
   * @param string $namespace
   *   Namespace to mock the function in.
   */
  protected function mockMail(array $response, string $namespace = 'DrevOps\\VortexTooling'): void {
    $this->mockMailMultiple([$response], $namespace);
  }

  /**
   * Verify all mocked mail responses were consumed.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all mocked responses were consumed.
   */
  protected function mockMailAssertAllMocksConsumed(): void {
    if ($this->mockMail !== NULL && !$this->mockMailChecked) {
      $this->mockMailChecked = TRUE;

      $total_responses = count($this->mockMailResponses);
      $consumed_responses = $this->mockMailIndex;

      if ($consumed_responses < $total_responses) {
        $this->fail(sprintf('Not all mocked mail responses were consumed. Expected %d call(s), but only %d call(s) were made.', $total_responses, $consumed_responses));
      }
    }
  }

  /**
   * Mock request functions to return predefined responses.
   *
   * @param array<int, array{url: string, method?: string, response: array{ok?: bool, status?: int, body?: string|false, error?: string|null, info?: array<string, mixed>}}> $responses
   *   Array of responses to return for each request call.
   *   Each response should have:
   *   - url: Expected URL (required)
   *   - method: Expected HTTP method (optional)
   *   - response: Response data (defaults applied internally).
   * @param string $namespace
   *   Namespace to mock the functions in (defaults to DrevOps\VortexTooling).
   *
   * @throws \RuntimeException
   *   When more requests are made than mocked responses available.
   */
  protected function mockRequestMultiple(array $responses, string $namespace = 'DrevOps\\VortexTooling'): void {
    // Normalize responses by applying defaults before storing.
    $normalized_responses = [];
    foreach ($responses as $response) {
      // @phpstan-ignore-next-line nullCoalesce.offset
      $response['response'] = ($response['response'] ?? []) + [
        'ok' => TRUE,
        'status' => 200,
        'body' => '',
        'error' => NULL,
        'info' => [],
      ];
      $normalized_responses[] = $response;
    }

    // Add normalized responses to the class property.
    $this->mockRequestResponses = array_merge($this->mockRequestResponses, $normalized_responses);

    // If mocks already exist, just add to responses and return.
    if (!empty($this->mockRequest)) {
      return;
    }

    // Track state across all request function calls.
    $current_url = NULL;
    $current_method = NULL;

    // Mock curl_init - stores URL and returns handle.
    $this->mockRequest['curl_init'] = $this->getFunctionMock($namespace, 'curl_init');
    $this->mockRequest['curl_init']->expects($this->any())
      ->willReturnCallback(function ($url = NULL) use (&$current_url): string {
        $total_responses = count($this->mockRequestResponses);

        if ($this->mockRequestIndex >= $total_responses) {
          throw new \RuntimeException(sprintf('curl_init() called more times than mocked responses. Expected %d request(s), but attempting request #%d.', $total_responses, $this->mockRequestIndex + 1));
        }

        $current_url = $url;
        return 'mock_curl_handle';
      });

    // Mock curl_setopt_array - extracts method from options.
    $this->mockRequest['curl_setopt_array'] = $this->getFunctionMock($namespace, 'curl_setopt_array');
    $this->mockRequest['curl_setopt_array']->expects($this->any())
      ->willReturnCallback(function ($ch, $options) use (&$current_method): bool {
        if (isset($options[CURLOPT_CUSTOMREQUEST])) {
          $current_method = $options[CURLOPT_CUSTOMREQUEST];
        }
        return TRUE;
      });

    // Mock curl_exec - validates and returns response body.
    $this->mockRequest['curl_exec'] = $this->getFunctionMock($namespace, 'curl_exec');
    $this->mockRequest['curl_exec']->expects($this->any())
      ->willReturnCallback(function () use (&$current_url, &$current_method): string|false {
        $total_responses = count($this->mockRequestResponses);

        // Note: This check is unreachable in normal flow since curl_init()
        // already validates the index. Kept as defensive programming for safety
        // in case the mock structure changes or curl_init is bypassed.
        // @codeCoverageIgnoreStart
        if ($this->mockRequestIndex >= $total_responses) {
          throw new \RuntimeException(sprintf('curl_exec() called more times than mocked responses. Expected %d request(s), but attempting request #%d.', $total_responses, $this->mockRequestIndex + 1));
        }
        // @codeCoverageIgnoreEnd
        $mock = $this->mockRequestResponses[$this->mockRequestIndex];

        // Capture current values before incrementing/resetting.
        $url_to_validate = $current_url;
        $method_to_validate = $current_method;

        // Increment index and reset state NOW, before validation.
        // This ensures the mock is marked as "consumed" even if validation
        // throws an exception.
        $this->mockRequestIndex++;
        $current_url = NULL;
        $current_method = NULL;

        // Validate response structure.
        // @phpstan-ignore-next-line isset.offset
        if (!isset($mock['url'])) {
          throw new \InvalidArgumentException('Mocked request response must include "url" key to specify expected URL.');
        }

        // Validate URL matches.
        if ($mock['url'] !== $url_to_validate) {
          throw new \RuntimeException(sprintf('request made to unexpected URL. Expected "%s", got "%s".', $mock['url'], $url_to_validate));
        }

        // Validate method if specified.
        if (isset($mock['method']) && $mock['method'] !== $method_to_validate) {
          throw new \RuntimeException(sprintf('request made with unexpected method. Expected "%s", got "%s".', $mock['method'], $method_to_validate ?? 'GET'));
        }

        // Response is already normalized with defaults.
        return $mock['response']['body'];
      });

    // Mock curl_errno - returns 0 for success or non-zero for error.
    // Note: Index is incremented in curl_exec, so we use previous index.
    $this->mockRequest['curl_errno'] = $this->getFunctionMock($namespace, 'curl_errno');
    $this->mockRequest['curl_errno']->expects($this->any())
      ->willReturnCallback(function (): int {
        // Use the previous index since curl_exec already incremented it.
        $mock = $this->mockRequestResponses[$this->mockRequestIndex - 1];
        // Response is already normalized with defaults.
        return $mock['response']['error'] !== NULL ? 1 : 0;
      });

    // Mock curl_error - returns error message if present.
    // Note: Index is incremented in curl_exec, so we use previous index.
    $this->mockRequest['curl_error'] = $this->getFunctionMock($namespace, 'curl_error');
    $this->mockRequest['curl_error']->expects($this->any())
      ->willReturnCallback(function (): string {
        // Use the previous index since curl_exec already incremented it.
        $mock = $this->mockRequestResponses[$this->mockRequestIndex - 1];
        // Response is already normalized with defaults.
        return $mock['response']['error'] ?? '';
      });

    // Mock curl_getinfo - returns info array with http_code.
    // Note: Index is incremented in curl_exec, not here.
    $this->mockRequest['curl_getinfo'] = $this->getFunctionMock($namespace, 'curl_getinfo');
    $this->mockRequest['curl_getinfo']->expects($this->any())
      ->willReturnCallback(function (): array {
        // Use the previous index since curl_exec already incremented it.
        $mock = $this->mockRequestResponses[$this->mockRequestIndex - 1];
        // Response is already normalized with defaults.
        return $mock['response']['info'] + ['http_code' => $mock['response']['status']];
      });
  }

  /**
   * Mock request() call with function-like signature.
   *
   * @param string $url
   *   URL to request.
   * @param array{method?: string, headers?: array<int, string>, body?: mixed, timeout?: int} $options
   *   Request options (method, headers, body, timeout).
   * @param array{ok?: bool, status?: int, body?: string|false, error?: string|null, info?: array<string, mixed>}|null $response
   *   Mock response data.
   * @param string $namespace
   *   Namespace to mock the functions in.
   */
  protected function mockRequest(
    string $url,
    array $options = [],
    ?array $response = NULL,
    string $namespace = 'DrevOps\\VortexTooling',
  ): void {
    // Determine method from options or default to GET.
    $method = $options['method'] ?? 'GET';

    // Convert to internal format.
    $this->mockRequestMultiple([[
      'url' => $url,
      'method' => $method,
      'response' => $response ?? [],
    ],
    ], $namespace);
  }

  /**
   * Mock request_get() call with function-like signature.
   *
   * @param string $url
   *   URL to request.
   * @param array<int, string> $headers
   *   Array of HTTP headers.
   * @param int $timeout
   *   Request timeout in seconds.
   * @param array{ok?: bool, status?: int, body?: string|false, error?: string|null, info?: array<string, mixed>}|null $response
   *   Mock response data.
   * @param string $namespace
   *   Namespace to mock the functions in.
   */
  protected function mockRequestGet(
    string $url,
    array $headers = [],
    int $timeout = 10,
    ?array $response = NULL,
    string $namespace = 'DrevOps\\VortexTooling',
  ): void {
    $this->mockRequestMultiple([[
      'url' => $url,
      'method' => 'GET',
      'response' => $response ?? [],
    ],
    ], $namespace);
  }

  /**
   * Mock request_post() call with function-like signature.
   *
   * @param string $url
   *   URL to request.
   * @param mixed $body
   *   Request body.
   * @param array<int, string> $headers
   *   Array of HTTP headers.
   * @param int $timeout
   *   Request timeout in seconds.
   * @param array{ok?: bool, status?: int, body?: string|false, error?: string|null, info?: array<string, mixed>}|null $response
   *   Mock response data.
   * @param string $namespace
   *   Namespace to mock the functions in.
   */
  protected function mockRequestPost(
    string $url,
    $body = NULL,
    array $headers = [],
    int $timeout = 10,
    ?array $response = NULL,
    string $namespace = 'DrevOps\\VortexTooling',
  ): void {
    $this->mockRequestMultiple([[
      'url' => $url,
      'method' => 'POST',
      'response' => $response ?? [],
    ],
    ], $namespace);
  }

  /**
   * Verify all mocked request responses were consumed.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all mocked responses were consumed.
   */
  protected function mockRequestAssertAllMocksConsumed(): void {
    if (!empty($this->mockRequestResponses) && !$this->mockRequestChecked) {
      $this->mockRequestChecked = TRUE;

      $total_responses = count($this->mockRequestResponses);
      $consumed_responses = $this->mockRequestIndex;

      if ($consumed_responses < $total_responses) {
        $this->fail(sprintf('Not all mocked request responses were consumed. Expected %d request(s), but only %d request(s) were made.', $total_responses, $consumed_responses));
      }
    }
  }

}
