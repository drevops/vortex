<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Traits;

use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
use phpmock\phpunit\PHPMock;

trait MockTrait {

  use PHPMock;

  /**
   * Unified registry of all mock objects indexed by function name.
   *
   * @var array<string, \PHPUnit\Framework\MockObject\MockObject|array<string, \PHPUnit\Framework\MockObject\MockObject>>
   */
  protected array $mocks = [];

  /**
   * Unified registry of all mock responses indexed by function name.
   *
   * @var array<string, array<int, array<string, mixed>>>
   */
  protected array $mockResponses = [];

  /**
   * Unified registry of response indices indexed by function name.
   *
   * @var array<string, int>
   */
  protected array $mockIndices = [];

  /**
   * Unified registry of checked flags indexed by function name.
   *
   * @var array<string, bool>
   */
  protected array $mockChecked = [];

  protected function mockTearDown(): void {
    // Assert all mocks consumed using unified infrastructure.
    foreach (array_keys($this->mocks) as $function_name) {
      $this->assertMockConsumed($function_name);
    }

    // Reset all mocks using unified infrastructure.
    foreach (array_keys($this->mocks) as $function_name) {
      $this->resetMock($function_name);
    }

    // Clear unified registries.
    $this->mocks = [];
    $this->mockResponses = [];
    $this->mockIndices = [];
    $this->mockChecked = [];
  }

  /**
   * Register a new mock function in the unified registry.
   *
   * @param string $function_name
   *   The function name to mock (e.g., 'passthru', 'mail').
   * @param string $namespace
   *   The namespace where the function should be mocked.
   * @param callable $callback
   *   The callback to execute when the mocked function is called.
   */
  protected function registerMock(string $function_name, string $namespace, callable $callback): void {
    // Create the mock object.
    $mock = $this->getFunctionMock($namespace, $function_name);
    $mock->expects($this->any())->willReturnCallback($callback);

    // Store in registry.
    $this->mocks[$function_name] = $mock;
  }

  /**
   * Add responses for a mock function.
   *
   * @param string $function_name
   *   The function name (e.g., 'passthru', 'mail').
   * @param array<int, array<string, mixed>> $responses
   *   Array of response configurations.
   */
  protected function addMockResponses(string $function_name, array $responses): void {
    // Initialize if not exists.
    if (!isset($this->mockResponses[$function_name])) {
      $this->mockResponses[$function_name] = [];
      $this->mockIndices[$function_name] = 0;
      $this->mockChecked[$function_name] = FALSE;
    }

    // Add responses to the queue.
    $this->mockResponses[$function_name] = array_merge(
      $this->mockResponses[$function_name],
      $responses
    );
  }

  /**
   * Get the next response for a mock function.
   *
   * @param string $function_name
   *   The function name.
   *
   * @return array<string, mixed>
   *   The next response configuration.
   *
   * @throws \RuntimeException
   *   When no more responses are available.
   */
  protected function getNextMockResponse(string $function_name): array {
    $total_responses = count($this->mockResponses[$function_name] ?? []);
    $current_index = $this->mockIndices[$function_name] ?? 0;

    if ($current_index >= $total_responses) {
      throw new \RuntimeException(sprintf(
        '%s() called more times than mocked responses. Expected %d call(s), but attempting call #%d.',
        $function_name,
        $total_responses,
        $current_index + 1
      ));
    }

    $response = $this->mockResponses[$function_name][$current_index];
    $this->mockIndices[$function_name]++;

    return $response;
  }

  /**
   * Assert all responses were consumed for a mock function.
   *
   * @param string $function_name
   *   The function name.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all responses were consumed.
   */
  protected function assertMockConsumed(string $function_name): void {
    // Skip if no mock exists for this function.
    if (!isset($this->mocks[$function_name])) {
      return;
    }

    // Skip if already checked.
    if (isset($this->mockChecked[$function_name]) && $this->mockChecked[$function_name]) {
      return;
    }

    $this->mockChecked[$function_name] = TRUE;

    $total_responses = count($this->mockResponses[$function_name] ?? []);
    $consumed_responses = $this->mockIndices[$function_name] ?? 0;

    if ($consumed_responses < $total_responses) {
      $this->fail(sprintf(
        'Not all mocked %s responses were consumed. Expected %d call(s), but only %d call(s) were made.',
        $function_name,
        $total_responses,
        $consumed_responses
      ));
    }
  }

  /**
   * Reset a mock function's state.
   *
   * @param string $function_name
   *   The function name.
   */
  protected function resetMock(string $function_name): void {
    unset($this->mocks[$function_name]);
    unset($this->mockResponses[$function_name]);
    unset($this->mockIndices[$function_name]);
    unset($this->mockChecked[$function_name]);
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
    // Add responses to unified registry.
    $this->addMockResponses('passthru', $responses);

    // If mock already exists, just add to responses and return.
    if (isset($this->mocks['passthru'])) {
      return;
    }

    // Register the mock using unified infrastructure.
    $this->registerMock('passthru', $namespace, function ($command, &...$args): null|false {
      $response = $this->getNextMockResponse('passthru');

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

      /** @var array{cmd: string, output: string, result_code: int, return: NULL|FALSE} $response */

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
    $this->assertMockConsumed('passthru');
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

    // Store in unified registry.
    $this->mocks['quit'] = $quit;
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
   *   - result: Return value (defaults to TRUE).
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

    // Add responses to unified registry.
    $this->addMockResponses('mail', $normalized_responses);

    // If mock already exists, just add to responses and return.
    if (isset($this->mocks['mail'])) {
      return;
    }

    // Register the mock using unified infrastructure.
    $this->registerMock('mail', $namespace, function (string $to, string $subject, string $message, array|string $additional_headers = [], string $additional_params = ''): bool {
      /** @var array<string, mixed> $response */
      $response = $this->getNextMockResponse('mail');

      // Validate response structure.
      if (!isset($response['to'])) {
        throw new \InvalidArgumentException('Mocked mail response must include "to" key to specify expected recipient.');
      }
      if (!isset($response['subject'])) {
        throw new \InvalidArgumentException('Mocked mail response must include "subject" key to specify expected subject.');
      }
      if (!isset($response['message'])) {
        throw new \InvalidArgumentException('Mocked mail response must include "message" key to specify expected message.');
      }

      /** @var array{to: string, subject: string, message: string, headers?: array<string>|string, result: bool} $response */

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

      // Validate headers if specified in response.
      if (isset($response['headers'])) {
        $expected_headers = $response['headers'];
        $actual_headers = $additional_headers;

        // Normalize both to arrays for comparison.
        if (is_string($expected_headers)) {
          $expected_headers = array_filter(array_map(trim(...), explode("\r\n", $expected_headers)));
        }
        if (is_string($actual_headers)) {
          $actual_headers = array_filter(array_map(trim(...), explode("\r\n", $actual_headers)));
        }

        // Sort both arrays for consistent comparison.
        sort($expected_headers);
        sort($actual_headers);

        if ($expected_headers !== $actual_headers) {
          throw new \RuntimeException(sprintf('mail() called with unexpected headers. Expected "%s", got "%s".', print_r($expected_headers, TRUE), print_r($actual_headers, TRUE)));
        }
      }

      return $response['result'];
    });
  }

  /**
   * Mock single mail call.
   *
   * @param array{to: string, subject: string, message: string, headers?: array<string>|string, result?: bool} $response
   *   Response with recipient, subject, message, optional headers, and result.
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
    $this->assertMockConsumed('mail');
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
      /** @var array{url: string, method?: string, response?: array<string, mixed>} $response */
      $response['response'] = ($response['response'] ?? []) + [
        'ok' => TRUE,
        'status' => 200,
        'body' => '',
        'error' => NULL,
        'info' => [],
      ];
      $normalized_responses[] = $response;
    }

    // Add responses to unified registry.
    $this->addMockResponses('request', $normalized_responses);

    // If mocks already exist, just add to responses and return.
    if (isset($this->mocks['request'])) {
      return;
    }

    // Track state across all request function calls.
    $current_url = NULL;
    $current_method = NULL;

    // Initialize the mocks array for request.
    $this->mocks['request'] = [];

    // Mock curl_init - stores URL and returns handle.
    $this->mocks['request']['curl_init'] = $this->getFunctionMock($namespace, 'curl_init');
    $this->mocks['request']['curl_init']->expects($this->any())
      ->willReturnCallback(function ($url = NULL) use (&$current_url): string {
        $total_responses = count($this->mockResponses['request']);

        if ($this->mockIndices['request'] >= $total_responses) {
          throw new \RuntimeException(sprintf('curl_init() called more times than mocked responses. Expected %d request(s), but attempting request #%d.', $total_responses, $this->mockIndices['request'] + 1));
        }

        $current_url = $url;
        return 'mock_curl_handle';
      });

    // Mock curl_setopt_array - extracts method from options.
    $this->mocks['request']['curl_setopt_array'] = $this->getFunctionMock($namespace, 'curl_setopt_array');
    $this->mocks['request']['curl_setopt_array']->expects($this->any())
      ->willReturnCallback(function ($ch, $options) use (&$current_method): bool {
        if (isset($options[CURLOPT_CUSTOMREQUEST])) {
          $current_method = $options[CURLOPT_CUSTOMREQUEST];
        }
        return TRUE;
      });

    // Mock curl_exec - validates and returns response body.
    $this->mocks['request']['curl_exec'] = $this->getFunctionMock($namespace, 'curl_exec');
    $this->mocks['request']['curl_exec']->expects($this->any())
      // @phpstan-ignore-next-line
      ->willReturnCallback(function () use (&$current_url, &$current_method): string|false {
        $total_responses = count($this->mockResponses['request']);

        // Note: This check is unreachable in normal flow since curl_init()
        // already validates the index. Kept as defensive programming for safety
        // in case the mock structure changes or curl_init is bypassed.
        // @codeCoverageIgnoreStart
        if ($this->mockIndices['request'] >= $total_responses) {
          throw new \RuntimeException(sprintf('curl_exec() called more times than mocked responses. Expected %d request(s), but attempting request #%d.', $total_responses, $this->mockIndices['request'] + 1));
        }
        // @codeCoverageIgnoreEnd
        /** @var array<string, mixed> $mock */
        $mock = $this->mockResponses['request'][$this->mockIndices['request']];

        // Capture current values before incrementing/resetting.
        $url_to_validate = $current_url;
        $method_to_validate = $current_method;

        // Increment index and reset state NOW, before validation.
        // This ensures the mock is marked as "consumed" even if validation
        // throws an exception.
        $this->mockIndices['request']++;
        $current_url = NULL;
        $current_method = NULL;

        // Validate response structure.
        if (!isset($mock['url'])) {
          throw new \InvalidArgumentException('Mocked request response must include "url" key to specify expected URL.');
        }

        /** @var array{url: string, method?: string, response: array{ok: bool, status: int, body: string, error: string|null, info: array<string, mixed>}} $mock */

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
    $this->mocks['request']['curl_errno'] = $this->getFunctionMock($namespace, 'curl_errno');
    $this->mocks['request']['curl_errno']->expects($this->any())
      ->willReturnCallback(function (): int {
        // Use the previous index since curl_exec already incremented it.
        /** @var array{url: string, method?: string, response: array{error: string|null}} $mock */
        $mock = $this->mockResponses['request'][$this->mockIndices['request'] - 1];
        // Response is already normalized with defaults.
        return $mock['response']['error'] !== NULL ? 1 : 0;
      });

    // Mock curl_error - returns error message if present.
    // Note: Index is incremented in curl_exec, so we use previous index.
    $this->mocks['request']['curl_error'] = $this->getFunctionMock($namespace, 'curl_error');
    $this->mocks['request']['curl_error']->expects($this->any())
      ->willReturnCallback(function (): string {
        // Use the previous index since curl_exec already incremented it.
        /** @var array{url: string, method?: string, response: array{error: string|null}} $mock */
        $mock = $this->mockResponses['request'][$this->mockIndices['request'] - 1];
        // Response is already normalized with defaults.
        return $mock['response']['error'] ?? '';
      });

    // Mock curl_getinfo - returns info array with http_code.
    // Note: Index is incremented in curl_exec, not here.
    $this->mocks['request']['curl_getinfo'] = $this->getFunctionMock($namespace, 'curl_getinfo');
    $this->mocks['request']['curl_getinfo']->expects($this->any())
      ->willReturnCallback(function (): array {
        // Use the previous index since curl_exec already incremented it.
        /** @var array{url: string, method?: string, response: array{info: array<string, mixed>, status: int}} $mock */
        $mock = $this->mockResponses['request'][$this->mockIndices['request'] - 1];
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
    $this->assertMockConsumed('request');
  }

  /**
   * Mock posix_isatty function to control terminal color detection.
   *
   * This mocks the underlying posix_isatty() function that
   * term_supports_color() calls, allowing tests to control whether color
   * output is enabled.
   *
   * @param bool $return_value
   *   The value to return (TRUE for TTY/color support, FALSE for no TTY).
   * @param string $namespace
   *   Namespace to mock the function in (defaults to DrevOps\VortexTooling).
   */
  protected function mockPosixIsatty(bool $return_value, string $namespace = 'DrevOps\\VortexTooling'): void {
    // Add single response to unified registry.
    $this->addMockResponses('posix_isatty', [['value' => $return_value]]);

    // Register mock if not already registered.
    if (!isset($this->mocks['posix_isatty'])) {
      $this->registerMock('posix_isatty', $namespace, function () {
        $response = $this->getNextMockResponse('posix_isatty');
        return $response['value'];
      });
    }
  }

  /**
   * Verify all mocked posix_isatty responses were consumed.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all mocked responses were consumed.
   */
  protected function mockPosixIsattyAssertAllMocksConsumed(): void {
    $this->assertMockConsumed('posix_isatty');
  }

  /**
   * Mock shell_exec() function.
   *
   * @param string|null|false $return_value
   *   The value to return from shell_exec() (e.g., '/usr/bin/docker').
   * @param string $namespace
   *   Namespace to mock the function in (defaults to DrevOps\VortexTooling).
   */
  protected function mockShellExec(string|null|false $return_value, string $namespace = 'DrevOps\\VortexTooling'): void {
    $this->mockShellExecMultiple([['value' => $return_value]], $namespace);
  }

  /**
   * Mock multiple shell_exec() calls with sequential return values.
   *
   * @param array<int, array{value: string|null|false}> $responses
   *   Array of shell_exec responses. Each response is an array with
   *   'value' key.
   * @param string $namespace
   *   Namespace to mock the function in (defaults to DrevOps\VortexTooling).
   *
   * @throws \RuntimeException
   *   When more shell_exec calls are made than mocked responses available.
   */
  protected function mockShellExecMultiple(array $responses, string $namespace = 'DrevOps\\VortexTooling'): void {
    // Add responses to unified registry.
    $this->addMockResponses('shell_exec', $responses);

    // If mocks already exist, just add to responses and return.
    if (isset($this->mocks['shell_exec'])) {
      return;
    }

    // Register the mock function.
    $this->registerMock('shell_exec', $namespace, function () {
      $response = $this->getNextMockResponse('shell_exec');
      return $response['value'];
    });
  }

  /**
   * Verify all mocked shell_exec responses were consumed.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   When not all mocked responses were consumed.
   */
  protected function mockShellExecAssertAllMocksConsumed(): void {
    $this->assertMockConsumed('shell_exec');
  }

}
