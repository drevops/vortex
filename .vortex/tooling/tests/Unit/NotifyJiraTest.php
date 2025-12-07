<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for notify-jira script.
 *
 * @phpcs:disable Drupal.Classes.FullyQualifiedNamespace.UseStatementMissing
 */
#[RunTestsInSeparateProcesses]
#[Group('notify')]
class NotifyJiraTest extends UnitTestCase {

  protected function setUp(): void {
    parent::setUp();

    $this->envSetMultiple([
      'VORTEX_NOTIFY_JIRA_PROJECT' => 'test-project',
      'VORTEX_NOTIFY_JIRA_USER_EMAIL' => 'user@example.com',
      'VORTEX_NOTIFY_JIRA_TOKEN' => 'test-token-123',
      'VORTEX_NOTIFY_JIRA_LABEL' => 'feature/TEST-123-test-feature',
      'VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL' => 'https://example.com',
      'VORTEX_NOTIFY_JIRA_LOGIN_URL' => 'https://example.com/login',
      'VORTEX_NOTIFY_JIRA_EVENT' => 'post_deployment',
      'VORTEX_NOTIFY_JIRA_ENDPOINT' => 'https://jira.example.com',
    ]);
  }

  public function testSuccessfulNotificationCommentOnly(): void {
    // Mock authentication check.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(function ($body): true {
        /** @var array {body: array {type: string}} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertArrayHasKey('body', $payload);
        $this->assertEquals('doc', $payload['body']['type']);
        return TRUE;
      }),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Started JIRA notification', $output);
    $this->assertStringContainsString('Found issue TEST-123', $output);
    $this->assertStringContainsString('Project        : test-project', $output);
    $this->assertStringContainsString('Issue          : TEST-123', $output);
    $this->assertStringContainsString('Posted comment with ID 10001', $output);
    $this->assertStringContainsString('Finished JIRA notification', $output);
  }

  public function testSuccessfulNotificationWithTransition(): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_TRANSITION', 'In Review');

    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    // Mock transition discovery.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/issue/TEST-123/transitions',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      [
        'status' => 200,
        'body' => '{"transitions": [{"id": "41", "name": "In Review"}]}',
      ]
    );

    // Mock transition application.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/transitions',
      $this->callback(function ($body): true {
        /** @var array {body: array {type: string, transition: array {id: string}}} $payload */
        $payload = json_decode($body, TRUE);
        $this->assertEquals('41', $payload['transition']['id']);
        return TRUE;
      }),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 204, 'body' => '']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Transitioning issue to In Review', $output);
    $this->assertStringContainsString('Discovering transition ID for In Review', $output);
    $this->assertStringContainsString('Transitioned issue to In Review', $output);
  }

  public function testSuccessfulNotificationWithAssignee(): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL', 'assignee@example.com');

    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    // Mock assignee discovery.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/user/assignable/search?query=assignee%40example.com&issueKey=TEST-123',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      [
        'status' => 200,
        'body' => '[{"accountId": "987654321098765432109876", "emailAddress": "assignee@example.com"}]',
      ]
    );

    // Mock assignee application (PUT request via request()).
    $this->mockRequest(
      'https://jira.example.com/rest/api/3/issue/TEST-123/assignee',
      [
        'method' => 'PUT',
        'body' => '{"accountId":"987654321098765432109876"}',
        'headers' => [
          'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
          'Content-Type: application/json',
        ],
      ],
      ['status' => 204, 'body' => '']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Assigning issue to assignee@example.com', $output);
    $this->assertStringContainsString('Discovering assignee user ID for assignee@example.com', $output);
    $this->assertStringContainsString('Assigned issue to assignee@example.com', $output);
  }

  public function testPreDeploymentEventSkipped(): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_EVENT', 'pre_deployment');

    $this->runScriptEarlyPass('src/notify-jira', 'Skipping JIRA notification for pre_deployment event');

  }

  public function testAuthenticationFailure(): void {
    // Mock authentication check returning invalid account ID.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "short"}']
    );

    $this->runScriptError('src/notify-jira', 'Unable to authenticate');

  }

  public function testCommentCreationFailure(): void {
    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation failure.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 400, 'body' => 'Bad Request']
    );

    $this->runScriptError('src/notify-jira', 'Unable to create a comment');

  }

  public function testTransitionDiscoveryFailure(): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_TRANSITION', 'Invalid Status');

    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    // Mock transition discovery returning empty list.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/issue/TEST-123/transitions',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"transitions": []}']
    );

    $this->runScriptError('src/notify-jira', 'Unable to retrieve transition ID');

  }

  public function testAssigneeDiscoveryFailure(): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL', 'nonexistent@example.com');

    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    // Mock assignee discovery returning empty list.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/user/assignable/search?query=nonexistent%40example.com&issueKey=TEST-123',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '[]']
    );

    $this->runScriptError('src/notify-jira', 'Unable to retrieve assignee account ID');

  }

  public function testSuccessfulNotificationWithCustomMessage(): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_MESSAGE', 'Custom deployment of %project% to %label% at %timestamp%');

    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Finished JIRA notification', $output);
  }

  #[DataProvider('dataProviderMissingRequiredVariables')]
  public function testMissingRequiredVariables(string $var_name): void {
    $this->envUnset($var_name);
    $this->runScriptError('src/notify-jira', 'Missing required value for ' . $var_name);
  }

  public static function dataProviderMissingRequiredVariables(): array {
    return [
      'project' => ['VORTEX_NOTIFY_JIRA_PROJECT'],
      'user_email' => ['VORTEX_NOTIFY_JIRA_USER_EMAIL'],
      'token' => ['VORTEX_NOTIFY_JIRA_TOKEN'],
      'label' => ['VORTEX_NOTIFY_JIRA_LABEL'],
      'environment_url' => ['VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL'],
    ];
  }

  public function testFallbackToGenericVariables(): void {
    $this->envUnsetMultiple([
      'VORTEX_NOTIFY_JIRA_PROJECT',
      'VORTEX_NOTIFY_JIRA_LABEL',
      'VORTEX_NOTIFY_JIRA_ENVIRONMENT_URL',
      'VORTEX_NOTIFY_JIRA_LOGIN_URL',
    ]);

    $this->envSet('VORTEX_NOTIFY_PROJECT', 'fallback-project');
    $this->envSet('VORTEX_NOTIFY_LABEL', 'feature/PROJ-456-fallback');
    $this->envSet('VORTEX_NOTIFY_ENVIRONMENT_URL', 'https://fallback.example.com');
    $this->envSet('VORTEX_NOTIFY_LOGIN_URL', 'https://fallback.example.com/login');

    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/PROJ-456/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Project        : fallback-project', $output);
    $this->assertStringContainsString('Deployment     : feature/PROJ-456-fallback', $output);
    $this->assertStringContainsString('Issue          : PROJ-456', $output);
    $this->assertStringContainsString('Environment URL: https://fallback.example.com', $output);
    $this->assertStringContainsString('Login URL      : https://fallback.example.com/login', $output);
  }

  public function testIssueExtractionFromLabel(): void {
    // Test with prefix in label.
    $this->envSet('VORTEX_NOTIFY_JIRA_LABEL', 'feature/ABC-123-description');

    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation for ABC-123.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/ABC-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Found issue ABC-123', $output);
  }

  public function testSuccessfulNotificationWithDefaultEndpoint(): void {
    $this->envUnset('VORTEX_NOTIFY_JIRA_ENDPOINT');

    // Mock authentication (default endpoint).
    $this->mockRequestGet(
      'https://jira.atlassian.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation (default endpoint).
    $this->mockRequestPost(
      'https://jira.atlassian.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Endpoint       : https://jira.atlassian.com', $output);
    $this->assertStringContainsString('Finished JIRA notification', $output);
  }

  public function testCompleteWorkflowWithAllFeatures(): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_TRANSITION', 'Done');
    $this->envSet('VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL', 'complete@example.com');

    // Mock authentication.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    // Mock comment creation.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    // Mock transition discovery.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/issue/TEST-123/transitions',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"transitions": [{"id": "31", "name": "Done"}]}']
    );

    // Mock transition application.
    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/transitions',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 204, 'body' => '']
    );

    // Mock assignee discovery.
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/user/assignable/search?query=complete%40example.com&issueKey=TEST-123',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '[{"accountId": "111111111111111111111111"}]']
    );

    // Mock assignee application.
    $this->mockRequest(
      'https://jira.example.com/rest/api/3/issue/TEST-123/assignee',
      [
        'method' => 'PUT',
        'body' => '{"accountId":"111111111111111111111111"}',
        'headers' => [
          'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
          'Content-Type: application/json',
        ],
      ],
      ['status' => 204, 'body' => '']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Posted comment with ID 10001', $output);
    $this->assertStringContainsString('Transitioned issue to Done', $output);
    $this->assertStringContainsString('Assigned issue to complete@example.com', $output);
    $this->assertStringContainsString('Finished JIRA notification', $output);
  }

  public function testLabelWithoutIssueNumber(): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_LABEL', 'main');

    $this->runScriptEarlyPass('src/notify-jira', 'Deployment label main does not contain issue number');
  }

  #[DataProvider('dataProviderAuthenticationFailures')]
  public function testAuthenticationFailures(string $response_body): void {
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => $response_body]
    );

    $this->runScriptError('src/notify-jira', 'Unable to authenticate');
  }

  public static function dataProviderAuthenticationFailures(): array {
    return [
      'missing account id' => ['{"email": "user@example.com"}'],
      'empty response body' => [''],
      'short account id' => ['{"accountId": "short"}'],
    ];
  }

  #[DataProvider('dataProviderCommentCreationFailures')]
  public function testCommentCreationFailures(string $response_body): void {
    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => $response_body]
    );

    $this->runScriptError('src/notify-jira', 'Unable to create a comment');
  }

  public static function dataProviderCommentCreationFailures(): array {
    return [
      'missing comment id' => ['{"created": "2024-01-01"}'],
      'empty response body' => [''],
      'non-numeric comment id' => ['{"id": "invalid"}'],
    ];
  }

  #[DataProvider('dataProviderTransitionDiscoveryFailures')]
  public function testTransitionDiscoveryFailures(string $response_body): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_TRANSITION', 'In Progress');

    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/issue/TEST-123/transitions',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => $response_body]
    );

    $this->runScriptError('src/notify-jira', 'Unable to retrieve transition ID');
  }

  public static function dataProviderTransitionDiscoveryFailures(): array {
    return [
      'empty response body' => [''],
      'non-array transitions' => ['{"transitions": "invalid"}'],
      'missing transition id' => ['{"transitions": [{"name": "In Progress"}]}'],
      'non-numeric transition id' => ['{"transitions": [{"id": "invalid", "name": "In Progress"}]}'],
    ];
  }

  #[DataProvider('dataProviderAssigneeDiscoveryFailures')]
  public function testAssigneeDiscoveryFailures(string $response_body): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_ASSIGNEE_EMAIL', 'assignee@example.com');

    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/user/assignable/search?query=assignee%40example.com&issueKey=TEST-123',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => $response_body]
    );

    $this->runScriptError('src/notify-jira', 'Unable to retrieve assignee account ID');
  }

  public static function dataProviderAssigneeDiscoveryFailures(): array {
    return [
      'non-array response' => ['{"error": "invalid"}'],
      'empty array' => ['[]'],
      'missing account id' => ['[{"emailAddress": "assignee@example.com"}]'],
      'short account id' => ['[{"accountId": "short"}]'],
    ];
  }

  public function testEventDefaultsToPostDeployment(): void {
    $this->envUnset('VORTEX_NOTIFY_JIRA_EVENT');
    $this->envUnset('VORTEX_NOTIFY_EVENT');

    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/TEST-123/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Finished JIRA notification', $output);
    $this->assertStringNotContainsString('Skipping JIRA notification', $output);
  }

  #[DataProvider('dataProviderIssueFormats')]
  public function testIssueFormats(string $label, string $expected_issue): void {
    $this->envSet('VORTEX_NOTIFY_JIRA_LABEL', $label);

    $this->mockRequestGet(
      'https://jira.example.com/rest/api/3/myself',
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 200, 'body' => '{"accountId": "123456789012345678901234"}']
    );

    $this->mockRequestPost(
      'https://jira.example.com/rest/api/3/issue/' . $expected_issue . '/comment',
      $this->callback(fn(): true => TRUE),
      [
        'Authorization: Basic ' . base64_encode('user@example.com:test-token-123'),
        'Content-Type: application/json',
      ],
      10,
      ['status' => 201, 'body' => '{"id": "10001"}']
    );

    $output = $this->runScript('src/notify-jira');

    $this->assertStringContainsString('Found issue ' . $expected_issue, $output);
  }

  public static function dataProviderIssueFormats(): array {
    return [
      'uppercase with dash' => ['ABC-123', 'ABC-123'],
      'lowercase project' => ['abc-456', 'abc-456'],
      'mixed case' => ['AbC-789', 'AbC-789'],
      'single digit' => ['PROJ-1', 'PROJ-1'],
      'many digits' => ['KEY-123456', 'KEY-123456'],
      'alphanumeric project' => ['P1R2-999', 'P1R2-999'],
      'with prefix path' => ['feature/XYZ-111', 'XYZ-111'],
      'with prefix and suffix' => ['feature/DEF-222-description', 'DEF-222'],
    ];
  }

}
