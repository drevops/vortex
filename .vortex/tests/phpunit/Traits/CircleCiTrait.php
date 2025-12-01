<?php

declare(strict_types=1);

namespace DrevOps\Vortex\Tests\Traits;

/**
 * Helper methods for CircleCI API interactions.
 */
trait CircleCiTrait {

  /**
   * Get the workflow ID from a job number.
   *
   * @param int $job_number
   *   The job number.
   *
   * @return string
   *   The workflow ID.
   */
  protected function circleCiGetWorkflowIdFromJobNumber(int $job_number): string {
    $token = getenv('TEST_CIRCLECI_TOKEN');
    $username = getenv('CIRCLE_PROJECT_USERNAME');
    $reponame = getenv('CIRCLE_PROJECT_REPONAME');

    if (!$token || !$username || !$reponame) {
      throw new \RuntimeException('Required CircleCI environment variables are not set.');
    }

    $url = sprintf(
      'https://circleci.com/api/v2/project/gh/%s/%s/job/%d',
      $username,
      $reponame,
      $job_number
    );

    $response = $this->circleCiApiRequest($url, $token);
    $data = json_decode((string) $response, TRUE, 512, JSON_THROW_ON_ERROR);

    if (!is_array($data)) {
      throw new \RuntimeException('Invalid CircleCI API response structure.');
    }

    if (empty($data['latest_workflow'] ?? []) || (is_array($data['latest_workflow']) && empty($data['latest_workflow']['id'] ?? ''))) {
      throw new \RuntimeException('Unable to retrieve workflow ID from CircleCI API response.');
    }

    return (string) $data['latest_workflow']['id'];
  }

  /**
   * Get numbers of previous jobs that current job depends on.
   *
   * @param int $current_job_number
   *   The current job number.
   *
   * @return array<int>
   *   Array of previous job numbers.
   */
  protected function circleCiGetPreviousJobNumbers(int $current_job_number): array {
    $token = getenv('TEST_CIRCLECI_TOKEN');

    if (!$token) {
      throw new \RuntimeException('TEST_CIRCLECI_TOKEN environment variable is not set.');
    }

    $workflow_id = $this->circleCiGetWorkflowIdFromJobNumber($current_job_number);

    $url = sprintf('https://circleci.com/api/v2/workflow/%s/job', $workflow_id);
    $response = $this->circleCiApiRequest($url, $token);
    $workflow_data = json_decode((string) $response, TRUE, 512, JSON_THROW_ON_ERROR);

    if (!is_array($workflow_data)) {
      throw new \RuntimeException('Invalid CircleCI API response structure.');
    }

    if (empty($workflow_data['items'] ?? [])) {
      throw new \RuntimeException('Unable to retrieve workflow jobs from CircleCI API response.');
    }

    if (!is_array($workflow_data['items'])) {
      throw new \RuntimeException('Invalid workflow jobs data structure from CircleCI API response.');
    }

    // Find the current job and get its dependencies.
    $dependencies_job_ids = [];
    foreach ($workflow_data['items'] as $item) {
      if (!is_array($item)) {
        continue;
      }

      if (($item['job_number'] ?? '') == $current_job_number) {
        $dependencies_job_ids = $item['dependencies'] ?? [];
        break;
      }
    }

    // Map dependency IDs to job numbers.
    $previous_job_numbers = [];
    foreach ($dependencies_job_ids as $dependency_job_id) {
      foreach ($workflow_data['items'] as $item) {
        if (!is_array($item)) {
          continue;
        }

        if ($item['id'] === $dependency_job_id) {
          $previous_job_numbers[] = (int) $item['job_number'];
          break;
        }
      }
    }

    return $previous_job_numbers;
  }

  /**
   * Get artifacts for a job.
   *
   * @param int $job_number
   *   The job number.
   *
   * @return array<mixed>
   *   Array of artifacts data.
   */
  protected function circleCiGetJobArtifacts(int $job_number): array {
    $token = getenv('TEST_CIRCLECI_TOKEN');
    $username = getenv('CIRCLE_PROJECT_USERNAME');
    $reponame = getenv('CIRCLE_PROJECT_REPONAME');

    if (!$token || !$username || !$reponame) {
      throw new \RuntimeException('Required CircleCI environment variables are not set.');
    }

    $url = sprintf(
      'https://circleci.com/api/v2/project/gh/%s/%s/%d/artifacts',
      $username,
      $reponame,
      $job_number
    );

    $response = $this->circleCiApiRequest($url, $token);

    $data = json_decode((string) $response, TRUE, 512, JSON_THROW_ON_ERROR);

    if (!is_array($data)) {
      throw new \RuntimeException('Invalid CircleCI API response structure.');
    }

    return $data;
  }

  /**
   * Get test metadata for a job.
   *
   * @param int $job_number
   *   The job number.
   *
   * @return array<mixed>
   *   Array of test metadata.
   */
  protected function circleCiGetJobTestMetadata(int $job_number): array {
    $token = getenv('TEST_CIRCLECI_TOKEN');
    $username = getenv('CIRCLE_PROJECT_USERNAME');
    $reponame = getenv('CIRCLE_PROJECT_REPONAME');

    if (!$token || !$username || !$reponame) {
      throw new \RuntimeException('Required CircleCI environment variables are not set.');
    }

    $url = sprintf(
      'https://circleci.com/api/v2/project/gh/%s/%s/%d/tests',
      $username,
      $reponame,
      $job_number
    );

    $response = $this->circleCiApiRequest($url, $token);

    $data = json_decode((string) $response, TRUE, 512, JSON_THROW_ON_ERROR);

    if (!is_array($data)) {
      throw new \RuntimeException('Invalid CircleCI API response structure.');
    }

    return $data;
  }

  /**
   * Make a CircleCI API request.
   *
   * @param string $url
   *   The API URL.
   * @param string $token
   *   The CircleCI API token.
   *
   * @return string
   *   The response body.
   */
  protected function circleCiApiRequest(string $url, string $token): string {
    if (empty($url) || empty($token)) {
      throw new \InvalidArgumentException('URL and token must be provided for CircleCI API request.');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Circle-Token: ' . $token,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
      throw new \RuntimeException(sprintf('CircleCI API request failed with HTTP code %d: %s', $http_code, $response));
    }

    if ($response === FALSE) {
      throw new \RuntimeException('CircleCI API request failed: no response received.');
    }

    return (string) $response;
  }

  /**
   * Extract artifact paths for a specific node index.
   *
   * @param array<mixed> $artifacts_data
   *   The artifacts data from CircleCI API.
   * @param int $node_index
   *   The node index (for parallel jobs).
   *
   * @return array<string>
   *   Array of artifact paths.
   */
  protected function circleCiExtractArtifactPaths(array $artifacts_data, int $node_index): array {
    $paths = [];

    if (empty($artifacts_data['items']) || !is_array($artifacts_data['items'])) {
      return $paths;
    }

    foreach ($artifacts_data['items'] as $item) {
      if (!is_array($item)) {
        continue;
      }

      if (($item['node_index'] ?? '') === $node_index) {
        $paths[] = $item['path'];
      }
    }

    return $paths;
  }

  /**
   * Extract test file paths from test metadata.
   *
   * @param array<mixed> $tests_data
   *   The test metadata from CircleCI API.
   *
   * @return array<string>
   *   Array of test file paths.
   */
  protected function circleCiExtractTestPaths(array $tests_data): array {
    $paths = [];

    if (empty($tests_data['items']) || !is_array($tests_data['items'])) {
      return $paths;
    }

    foreach ($tests_data['items'] as $item) {
      if (!is_array($item)) {
        continue;
      }

      if (!is_string($item['file'])) {
        continue;
      }

      $paths[] = $item['file'];
    }

    return $paths;
  }

}
