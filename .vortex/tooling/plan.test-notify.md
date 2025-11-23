# Plan: Comprehensive Testing for Vortex Notify Scripts

## Objective

Create comprehensive unit tests for all 6 notify scripts to achieve 95-100% code coverage using the existing MockTrait infrastructure.

## Scope

Test all notify scripts in `.vortex/tooling/src/`:
- `notify-webhook` (140 lines)
- `notify-slack` (151 lines)
- `notify-email` (162 lines)
- `notify-github` (165 lines)
- `notify-newrelic` (176 lines)
- `notify-jira` (285 lines)

**Total:** ~1,079 lines of code to test

## Testing Strategy

### Mock Infrastructure

Use existing `MockTrait` system for:
- **HTTP Requests**: `mockRequestGet()`, `mockRequestPost()`, `mockRequest()`
- **Exit Behavior**: `mockQuit()`
- **Shell Commands**: `mockPassthru()`

### Test Organization

Create one test class per script in `tests/Unit/`:
- `NotifyWebhookTest.php`
- `NotifySlackTest.php`
- `NotifyEmailTest.php`
- `NotifyGithubTest.php`
- `NotifyNewrelicTest.php`
- `NotifyJiraTest.php`

### Test Coverage Requirements

Each test class must include scenarios for:

1. **Success Paths**
   - Valid configuration with all required variables
   - Successful HTTP requests
   - Proper output formatting
   - Correct exit codes

2. **Validation Tests**
   - Missing required environment variables
   - Each required variable tested individually
   - Proper error messages

3. **Event Type Handling**
   - Pre-deployment events (skip scenarios)
   - Post-deployment events (full execution)

4. **HTTP Interaction Tests**
   - Successful requests (2xx responses)
   - Client errors (4xx responses)
   - Server errors (5xx responses)
   - Network failures
   - Timeout scenarios

5. **Token Replacement**
   - Verify all tokens are replaced
   - Test with various token values
   - Special characters in tokens
   - Unicode handling

6. **Configuration Variations**
   - Different HTTP methods (GET, POST, PUT)
   - Multiple headers parsing
   - Custom payloads
   - Optional parameters

7. **Override Execution**
   - `VORTEX_TOOLING_CUSTOM_DIR` handling
   - Script override detection

8. **Edge Cases**
   - Empty values
   - Very long values
   - Special characters
   - JSON escaping
   - URL encoding

## Implementation Approach

### Phase 1: Script Analysis

For each script, analyze:
1. Required environment variables
2. Optional environment variables with defaults
3. HTTP endpoints and methods
4. Request/response formats
5. Success/failure conditions
6. Token replacement patterns

### Phase 2: Test Implementation

For each script:

1. **Create test class**
   ```php
   <?php

   namespace DrevOps\VortexTooling\Tests\Unit;

   use DrevOps\VortexTooling\Tests\Exceptions\QuitSuccessException;
   use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
   use PHPUnit\Framework\Attributes\CoversNothing;
   use PHPUnit\Framework\Attributes\DataProvider;

   #[CoversNothing]
   class NotifyWebhookTest extends UnitTestCase {

     protected function setUp(): void {
       parent::setUp();
       require_once __DIR__ . '/../../src/helpers.php';
     }

     // Test methods...
   }
   ```

2. **Implement success path test**
   - Set all required environment variables
   - Mock successful HTTP request
   - Mock quit(0)
   - Verify output contains success messages

3. **Implement validation tests**
   - Test each missing required variable
   - Verify error messages
   - Verify quit(1) is called

4. **Implement HTTP failure tests**
   - Mock various HTTP error responses
   - Verify error handling
   - Verify proper exit codes

5. **Implement token replacement tests**
   - Verify tokens are replaced in payloads
   - Test edge cases (special chars, unicode)

6. **Implement event type tests**
   - Pre-deployment: verify skip behavior
   - Post-deployment: verify full execution

### Phase 3: Coverage Verification

After implementing tests for each script:

1. **Run coverage analysis**
   ```bash
   composer test-coverage
   ```

2. **Review HTML coverage report**
   - Open `coverage/index.html`
   - Check per-script coverage percentage
   - Identify uncovered lines

3. **Add missing tests**
   - Target uncovered branches
   - Add edge case tests
   - Aim for 95-100% coverage

4. **Document hard-to-cover lines**
   - Unreachable code
   - System-dependent code
   - External dependency failures
   - Defensive checks

## Execution Order

Test scripts in order of increasing complexity:

### 1. notify-webhook (140 lines)
**Priority: First** - Simplest, establishes pattern

**Key aspects:**
- Generic webhook with custom payload
- Token replacement
- HTTP method configuration
- Header parsing

**Estimated tests:** 10-12

### 2. notify-slack (151 lines)
**Priority: Second** - Similar to webhook

**Key aspects:**
- Slack-specific payload format
- Webhook URL validation
- Channel/message formatting

**Estimated tests:** 10-12

### 3. notify-email (162 lines)
**Priority: Third** - Email sending

**Key aspects:**
- Email configuration
- SMTP or sendmail
- Recipient parsing
- Subject/body formatting

**Estimated tests:** 10-12

### 4. notify-github (165 lines)
**Priority: Fourth** - GitHub API

**Key aspects:**
- GitHub API authentication
- Repository context
- Commit status updates
- PR comments

**Estimated tests:** 12-14

### 5. notify-newrelic (176 lines)
**Priority: Fifth** - NewRelic API

**Key aspects:**
- NewRelic API authentication
- Deployment markers
- Application ID handling
- Revision tracking

**Estimated tests:** 12-14

### 6. notify-jira (285 lines)
**Priority: Last** - Most complex

**Key aspects:**
- JIRA API authentication
- Issue detection in labels
- Comment formatting (ADF)
- Issue transitions
- Assignee updates
- Multiple API endpoints

**Estimated tests:** 15-18

## Test Pattern Example

```php
/**
 * Test successful webhook notification.
 */
public function testSuccessfulWebhookNotification(): void {
  // Arrange - Set environment variables.
  putenv('VORTEX_NOTIFY_WEBHOOK_PROJECT=test-project');
  putenv('VORTEX_NOTIFY_WEBHOOK_LABEL=main');
  putenv('VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL=https://example.com');
  putenv('VORTEX_NOTIFY_WEBHOOK_LOGIN_URL=https://example.com/login');
  putenv('VORTEX_NOTIFY_WEBHOOK_URL=https://webhook.example.com');
  putenv('VORTEX_NOTIFY_WEBHOOK_METHOD=POST');
  putenv('VORTEX_NOTIFY_WEBHOOK_HEADERS=Content-type: application/json');
  putenv('VORTEX_NOTIFY_WEBHOOK_EVENT=post_deployment');

  // Mock HTTP request.
  $this->mockRequestPost(
    'https://webhook.example.com',
    $this->callback(function ($body) {
      // Verify payload contains replaced tokens.
      $this->assertStringContainsString('test-project', $body);
      $this->assertStringContainsString('main', $body);
      return true;
    }),
    ['Content-type: application/json'],
    10,
    ['status' => 200, 'body' => '{"success": true}']
  );

  // Mock successful exit.
  $this->mockQuit(0);
  $this->expectException(QuitSuccessException::class);

  // Act - Run the script.
  $output = $this->runScript('notify-webhook', 'src');

  // Assert - Verify output.
  $this->assertStringContainsString('Started Webhook notification', $output);
  $this->assertStringContainsString('Webhook notification has been sent', $output);
}

/**
 * Test missing required variable.
 */
public function testMissingProjectVariable(): void {
  // Arrange - Don't set VORTEX_NOTIFY_WEBHOOK_PROJECT.
  putenv('VORTEX_NOTIFY_WEBHOOK_PROJECT');
  putenv('VORTEX_NOTIFY_WEBHOOK_LABEL=main');
  putenv('VORTEX_NOTIFY_WEBHOOK_ENVIRONMENT_URL=https://example.com');
  putenv('VORTEX_NOTIFY_WEBHOOK_LOGIN_URL=https://example.com/login');
  putenv('VORTEX_NOTIFY_WEBHOOK_URL=https://webhook.example.com');

  // Mock error exit.
  $this->mockQuit(1);
  $this->expectException(QuitErrorException::class);

  // Act & Assert - Run script, expect failure.
  $output = $this->runScript('notify-webhook', 'src');
  $this->assertStringContainsString('Missing required value for variable VORTEX_NOTIFY_WEBHOOK_PROJECT', $output);
}
```

## Constraints

### DO NOT

- ❌ Run linting during test development
- ❌ Modify notify scripts themselves
- ❌ Create manual integration tests (use mocks only)
- ❌ Skip coverage verification

### DO

- ✅ Use `composer test-coverage` for coverage reports
- ✅ Use existing MockTrait infrastructure
- ✅ Test each script independently
- ✅ Use data providers for similar scenarios
- ✅ Document hard-to-cover edge cases
- ✅ Target 95-100% coverage per script

## Coverage Verification Process

After implementing each script's tests:

1. **Generate coverage report**
   ```bash
   composer test-coverage
   ```

2. **Open HTML report**
   ```bash
   open coverage/index.html
   ```

3. **Analyze coverage**
   - Check overall coverage percentage
   - Identify uncovered lines (red)
   - Identify partially covered lines (yellow)
   - Review branch coverage

4. **Add missing tests**
   - Target uncovered branches
   - Add tests for edge cases
   - Use data providers for variations

5. **Document exceptions**
   - If line cannot be covered, document why
   - Explain the scenario required
   - Justify why it's not testable

## Expected Hard-to-Cover Scenarios

Potential edge cases that may be difficult to achieve 100% coverage:

1. **System-dependent code**
   - File system operations that depend on OS
   - Permission checks

2. **Defensive programming**
   - Checks for impossible states
   - Never-reached fallbacks

3. **External dependencies**
   - Code that requires real external systems
   - Platform-specific behavior

4. **Error handling**
   - Rarely-triggered error paths
   - System-level failures

## Deliverables

### 1. Test Classes (6 files)

- `tests/Unit/NotifyWebhookTest.php`
- `tests/Unit/NotifySlackTest.php`
- `tests/Unit/NotifyEmailTest.php`
- `tests/Unit/NotifyGithubTest.php`
- `tests/Unit/NotifyNewrelicTest.php`
- `tests/Unit/NotifyJiraTest.php`

### 2. Test Coverage

- **Target:** 95-100% coverage per script
- **Method:** `composer test-coverage`
- **Report:** HTML coverage report in `coverage/`

### 3. Coverage Summary Document

Create `COVERAGE-NOTIFY.md` documenting:
- Coverage percentage per script
- Total test count per script
- Any lines that are hard to cover (with explanation)
- Overall coverage statistics

### 4. Edge Case Documentation

If any script has < 100% coverage, document:
- Which lines are uncovered
- Why they cannot be covered
- What scenario would be required to cover them
- Whether coverage gap is acceptable

## Success Criteria

- ✅ All 6 notify scripts have test classes
- ✅ Each script has 95-100% code coverage
- ✅ All tests pass
- ✅ MockTrait successfully mocks all external dependencies
- ✅ Coverage report generated
- ✅ Edge cases documented (if < 100% coverage)
- ✅ No linting run during implementation

## Timeline Estimate

- **notify-webhook:** 1-2 hours (pilot implementation)
- **notify-slack:** 1-2 hours (similar pattern)
- **notify-email:** 1-2 hours
- **notify-github:** 2-3 hours (API complexity)
- **notify-newrelic:** 2-3 hours (API complexity)
- **notify-jira:** 3-4 hours (most complex)

**Total estimated time:** 10-16 hours

## Notes

- This plan focuses exclusively on unit testing with mocks
- No integration testing with real external services
- All HTTP requests will be mocked
- All exit() calls will be mocked
- Tests should be fast and isolated
- Coverage is measured, not enforced by CI (yet)

## Approval Required

Please review and approve this plan before implementation begins.

---

**Plan Status:** ⏳ Awaiting Approval

**Implementation will begin with:** `notify-webhook` (pilot script)
