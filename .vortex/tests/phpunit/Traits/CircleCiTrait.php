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
   * @param int $jobNumber
   *   The job number.
   *
   * @return string
   *   The workflow ID.
   */
  protected function circleCiGetWorkflowIdFromJobNumber(int $jobNumber): string {
    $token = getenv('TEST_CIRCLECI_TOKEN');
    $username = getenv('CIRCLE_PROJECT_USERNAME');
    $reponame = getenv('CIRCLE_PROJECT_REPONAME');

    $url = sprintf(
      'https://circleci.com/api/v2/project/gh/%s/%s/job/%d',
      $username,
      $reponame,
      $jobNumber
    );

    $response = $this->circleCiApiRequest($url, $token);
    $data = json_decode($response, TRUE, 512, JSON_THROW_ON_ERROR);

    return $data['latest_workflow']['id'];
  }

  /**
   * Get numbers of previous jobs that current job depends on.
   *
   * @param int $currentJobNumber
   *   The current job number.
   *
   * @return array<int>
   *   Array of previous job numbers.
   */
  protected function circleCiGetPreviousJobNumbers(int $currentJobNumber): array {
    $token = getenv('TEST_CIRCLECI_TOKEN');
    $workflowId = $this->circleCiGetWorkflowIdFromJobNumber($currentJobNumber);

    $url = sprintf('https://circleci.com/api/v2/workflow/%s/job', $workflowId);
    $response = $this->circleCiApiRequest($url, $token);
    $workflowData = json_decode($response, TRUE, 512, JSON_THROW_ON_ERROR);

    // Find the current job and get its dependencies.
    $dependenciesJobIds = [];
    foreach ($workflowData['items'] as $item) {
      if ($item['job_number'] == $currentJobNumber) {
        $dependenciesJobIds = $item['dependencies'] ?? [];
        break;
      }
    }

    // Map dependency IDs to job numbers.
    $previousJobNumbers = [];
    foreach ($dependenciesJobIds as $dependencyId) {
      foreach ($workflowData['items'] as $item) {
        if ($item['id'] === $dependencyId) {
          $previousJobNumbers[] = $item['job_number'];
          break;
        }
      }
    }

    return $previousJobNumbers;
  }

  /**
   * Get artifacts for a job.
   *
   * @param int $jobNumber
   *   The job number.
   *
   * @return array<mixed>
   *   Array of artifacts data.
   */
  protected function circleCiGetJobArtifacts(int $jobNumber): array {
    $token = getenv('TEST_CIRCLECI_TOKEN');
    $username = getenv('CIRCLE_PROJECT_USERNAME');
    $reponame = getenv('CIRCLE_PROJECT_REPONAME');

    $url = sprintf(
      'https://circleci.com/api/v2/project/gh/%s/%s/%d/artifacts',
      $username,
      $reponame,
      $jobNumber
    );

    $response = $this->circleCiApiRequest($url, $token);

    return json_decode($response, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  /**
   * Get test metadata for a job.
   *
   * @param int $jobNumber
   *   The job number.
   *
   * @return array<mixed>
   *   Array of test metadata.
   */
  protected function circleCiGetJobTestMetadata(int $jobNumber): array {
    $token = getenv('TEST_CIRCLECI_TOKEN');
    $username = getenv('CIRCLE_PROJECT_USERNAME');
    $reponame = getenv('CIRCLE_PROJECT_REPONAME');

    $url = sprintf(
      'https://circleci.com/api/v2/project/gh/%s/%s/%d/tests',
      $username,
      $reponame,
      $jobNumber
    );

    $response = $this->circleCiApiRequest($url, $token);

    return json_decode($response, TRUE, 512, JSON_THROW_ON_ERROR);
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
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Circle-Token: ' . $token,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
      throw new \RuntimeException(sprintf('CircleCI API request failed with HTTP code %d: %s', $httpCode, $response));
    }

    return $response;
  }

  /**
   * Extract artifact paths for a specific node index.
   *
   * @param array<mixed> $artifactsData
   *   The artifacts data from CircleCI API.
   * @param int $nodeIndex
   *   The node index (for parallel jobs).
   *
   * @return array<string>
   *   Array of artifact paths.
   */
  protected function circleCiExtractArtifactPaths(array $artifactsData, int $nodeIndex): array {
    $paths = [];
    foreach ($artifactsData['items'] as $item) {
      if ($item['node_index'] === $nodeIndex) {
        $paths[] = $item['path'];
      }
    }

    return $paths;
  }

  /**
   * Extract test file paths from test metadata.
   *
   * @param array<mixed> $testsData
   *   The test metadata from CircleCI API.
   *
   * @return array<string>
   *   Array of test file paths.
   */
  protected function circleCiExtractTestPaths(array $testsData): array {
    $paths = [];
    foreach ($testsData['items'] as $item) {
      $paths[] = $item['file'];
    }

    return $paths;
  }

}
