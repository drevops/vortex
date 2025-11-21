# Vortex Scripts Bash → PHP Conversion Plan

## Overview

Convert all Vortex bash scripts (`scripts/vortex/*.sh`) to PHP scripts as a standalone Composer package `drevops/vortex-tooling`.

## Requirements

### Script Structure
- **No file extensions** (e.g., `notify` not `notify.php`)
- **Shebang line**: `#!/usr/bin/env php`
- **Execute permissions**: `chmod +x`
- **Override support**: Via `$OVERRIDE_DIR` environment variable
- **Include helpers**: Every script includes `helpers.php`

### Package Structure
```
vortex-tooling/
├── composer.json
├── README.md
└── src/                        # All source code
    ├── helpers.php             # Shared helper functions
    ├── notify                  # Router script
    ├── notify-slack            # Channel-specific scripts
    ├── notify-email
    ├── notify-jira
    ├── notify-github
    ├── notify-newrelic
    ├── notify-webhook
    ├── provision
    ├── deploy
    ├── deploy-artifact
    ├── deploy-lagoon
    ├── deploy-container-registry
    └── ... (all other scripts)
```

### Coding Style

Scripts should be **readable but compact**:

- **Use ternary operators** for simple variable assignments with defaults
  ```php
  $var = getenv('VAR') ?: 'default';
  ```

- **Keep single-line code on one line** - no artificial line breaks for simple statements
  ```php
  // ✓ Good
  require_env('VORTEX_NOTIFY_SLACK_PROJECT');

  // ✗ Avoid unnecessary multi-line
  require_env(
    'VORTEX_NOTIFY_SLACK_PROJECT'
  );
  ```

- **Use array shorthand** where appropriate
  ```php
  $data = ['key' => 'value'];  // Not array('key' => 'value')
  ```

- **Minimize vertical space** - keep related code together without excessive blank lines

- **Prioritize clarity** - use descriptive variable names, avoid clever tricks

- **String quotes**: Use single quotes by default, double quotes only when necessary
  ```php
  // ✓ Good - single quotes for simple strings
  $var = 'default';
  info('Started notification');

  // ✓ Good - double quotes for interpolation
  info("Started $event notification");

  // ✓ Good - double quotes when string contains single quote
  $message = "It's an automated message";

  // ✗ Avoid - unnecessary double quotes
  $var = "default";
  info("Started notification");
  ```

## Phase 1: Foundation (helpers.php)

### 1.1 Create helpers.php

**Location:** `vortex-tooling/src/helpers.php`

**Must include:**

#### Environment Loading
```php
function load_env() {
  // Load .env file
  // Load .env.local if exists
  // Merge with existing environment
}
```

#### Color Output Functions
Based on current bash implementation:
```bash
note() { printf "       %s\n" "${1}"; }
task() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[34m[TASK] %s\033[0m\n" "${1}" || printf "[TASK] %s\n" "${1}"; }
info() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[36m[INFO] %s\033[0m\n" "${1}" || printf "[INFO] %s\n" "${1}"; }
pass() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[32m[ OK ] %s\033[0m\n" "${1}" || printf "[ OK ] %s\n" "${1}"; }
fail() { [ "${TERM:-}" != "dumb" ] && tput colors >/dev/null 2>&1 && printf "\033[31m[FAIL] %s\033[0m\n" "${1}" || printf "[FAIL] %s\n" "${1}"; }
```

Convert to PHP:
```php
function note($message) { echo "       $message\n"; }
function task($message) { /* Blue [TASK] with color detection */ }
function info($message) { /* Cyan [INFO] with color detection */ }
function pass($message) { /* Green [ OK ] with color detection */ }
function fail($message) { /* Red [FAIL] with color detection */ }
```

**Color detection logic:**
- Check if `TERM` env var is set and not "dumb"
- Check if terminal supports colors (can use `posix_isatty(STDOUT)`)
- Use ANSI codes if colors supported, plain text otherwise

#### Override System
```php
function execute_override_if_exists($script_name) {
  $override_dir = getenv('OVERRIDE_DIR');
  if (!$override_dir) return;

  $override_path = $override_dir . '/' . $script_name;
  if (file_exists($override_path) && is_executable($override_path)) {
    passthru("\"$override_path\"", $exit_code);
    exit($exit_code);
  }
}
```

#### Command Execution
```php
function execute_command($command, &$exit_code = null) {
  // Wrapper around passthru with error handling
}
```

#### Validation Helpers
```php
function require_env($var_name, $message = null) {
  $value = getenv($var_name);
  if (empty($value)) {
    fail($message ?? "Missing required value for $var_name");
    exit(1);
  }
  return $value;
}

function require_command($command) {
  exec("command -v $command", $output, $return_var);
  if ($return_var !== 0) {
    fail("Command $command is not available");
    exit(1);
  }
}
```

#### Debug Support
```php
function is_debug_enabled() {
  return getenv('VORTEX_DEBUG') === '1';
}
```

### 1.2 Test helpers.php

Create `test-helpers` script to validate all helper functions work correctly.

## Phase 2: Convert notify.sh (Router Script)

### 2.1 Analysis

**Current structure:**
```bash
# Router that calls channel-specific scripts
if [ -z "${VORTEX_NOTIFY_CHANNELS##*email*}" ]; then
  ./scripts/vortex/notify-email.sh "$@"
fi
```

**Key features:**
- Loads environment variables
- Validates required vars
- Routes to channel-specific scripts based on `VORTEX_NOTIFY_CHANNELS`
- Auto-generates `LOGIN_URL` if not provided
- Exports variables for child scripts

### 2.2 Conversion Steps

1. Create `vortex-tooling/src/notify`
2. Add shebang: `#!/usr/bin/env php`
3. Include helpers: `require_once __DIR__ . '/helpers.php';`
4. Add override check: `execute_override_if_exists(basename(__FILE__));`
5. Load environment
6. Validate required variables
7. Route to channel scripts using `passthru()`

### 2.3 PHP Structure

```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/helpers.php';
execute_override(basename(__FILE__));

// Load environment
load_dotenv();

// Get configuration
$channels = getenv('VORTEX_NOTIFY_CHANNELS') ?: 'email';
$event = getenv('VORTEX_NOTIFY_EVENT') ?: 'post_deployment';
$skip = getenv('VORTEX_NOTIFY_SKIP');

info("Started dispatching notifications.");

if ($skip) {
  pass("Skipping dispatching notifications.");
  exit(0);
}

// Validate required variables
validate_variable('VORTEX_NOTIFY_LABEL');
validate_variable('VORTEX_NOTIFY_ENVIRONMENT_URL');

// Auto-generate LOGIN_URL
if (!getenv('VORTEX_NOTIFY_LOGIN_URL')) {
  $env_url = getenv('VORTEX_NOTIFY_ENVIRONMENT_URL');
  putenv("VORTEX_NOTIFY_LOGIN_URL=$env_url/user/login");
}

// Validate event type
if (!in_array($event, ['pre_deployment', 'post_deployment'])) {
  fail("Unsupported event $event provided.");
  exit(1);
}

// Route to channel-specific scripts
$channels_array = explode(',', $channels);

foreach ($channels_array as $channel) {
  $channel = trim($channel);
  $script = __DIR__ . "/notify-$channel";

  if (file_exists($script)) {
    passthru("\"$script\"", $exit_code);
    if ($exit_code !== 0) {
      fail("Notification to $channel failed.");
    }
  }
}

pass("Finished dispatching notifications.");
```

## Phase 3: Convert notify-slack.sh

### 3.1 Analysis

**Key features:**
- Loads many environment variables with defaults
- Validates required variables
- Builds Slack message payload (JSON)
- Uses PHP for string replacements (already!)
- Makes curl request to Slack webhook
- Handles pre/post deployment events differently

### 3.2 Conversion Strategy

**Current bash approach:**
```bash
fallback_message=$(REPLACEMENT="${VORTEX_NOTIFY_SLACK_PROJECT}" TEMPLATE="${fallback_message}" php -r 'echo str_replace("%project%", getenv("REPLACEMENT"), getenv("TEMPLATE"));')
```

**New PHP approach:**
```php
$fallback_message = str_replace('%project%', $project, $template);
```

Much simpler in PHP!

### 3.3 PHP Structure

```php
#!/usr/bin/env php
<?php
require_once __DIR__ . '/helpers.php';
execute_override(basename(__FILE__));

load_dotenv();

// Get configuration with defaults
$project = getenv('VORTEX_NOTIFY_SLACK_PROJECT') ?: getenv('VORTEX_NOTIFY_PROJECT');
$label = getenv('VORTEX_NOTIFY_SLACK_LABEL') ?: getenv('VORTEX_NOTIFY_LABEL');
$env_url = getenv('VORTEX_NOTIFY_SLACK_ENVIRONMENT_URL') ?: getenv('VORTEX_NOTIFY_ENVIRONMENT_URL');
$login_url = getenv('VORTEX_NOTIFY_SLACK_LOGIN_URL') ?: getenv('VORTEX_NOTIFY_LOGIN_URL');
$event = getenv('VORTEX_NOTIFY_SLACK_EVENT') ?: getenv('VORTEX_NOTIFY_EVENT') ?: 'post_deployment';
$webhook = getenv('VORTEX_NOTIFY_SLACK_WEBHOOK');
$channel = getenv('VORTEX_NOTIFY_SLACK_CHANNEL');
$username = getenv('VORTEX_NOTIFY_SLACK_USERNAME') ?: 'Deployment Bot';
$icon = getenv('VORTEX_NOTIFY_SLACK_ICON_EMOJI') ?: ':rocket:';

// Validate required
validate_command('curl');
validate_variable('VORTEX_NOTIFY_SLACK_PROJECT');
validate_variable('VORTEX_NOTIFY_SLACK_WEBHOOK');
// ... etc

info("Started Slack notification.");

// Build message
$timestamp = date('d/m/Y H:i:s T');
$message = getenv('VORTEX_NOTIFY_SLACK_MESSAGE') ?: $default_template;

// Token replacement (much simpler in PHP!)
$message = str_replace([
  '%project%',
  '%label%',
  '%timestamp%',
  '%environment_url%',
  '%login_url%'
], [
  $project,
  $label,
  $timestamp,
  $env_url,
  $login_url
], $message);

// Determine color based on event
$color = $event === 'pre_deployment' ? '#808080' : 'good';
$event_label = $event === 'pre_deployment' ? 'Deployment Starting' : 'Deployment Complete';

// Build JSON payload
$payload = [
  'channel' => $channel,
  'username' => $username,
  'icon_emoji' => $icon,
  'attachments' => [[
    'fallback' => $message,
    'color' => $color,
    'title' => "$event_label: $project",
    // ... more fields
  ]]
];

$json = json_encode($payload);

// Send to Slack
$exit_code = 0;
passthru("curl -X POST -H 'Content-type: application/json' --data " . escapeshellarg($json) . " " . escapeshellarg($webhook), $exit_code);

if ($exit_code === 0) {
  pass("Sent Slack notification.");
} else {
  fail("Failed to send Slack notification.");
  exit(1);
}
```

## Phase 4: Convert Other notify-* Scripts

Apply same pattern to:
- `notify-email`
- `notify-jira`
- `notify-github`
- `notify-newrelic`
- `notify-webhook`

Each follows similar structure:
1. Load config
2. Validate required vars
3. Build payload
4. Make HTTP request
5. Report success/failure

## Phase 5: Convert Remaining Scripts (Priority Order)

### High Priority (Frequently Used)
1. `provision.sh` - Main provisioning
2. `deploy.sh` - Main deployment router
3. `deploy-artifact.sh`
4. `deploy-lagoon.sh`
5. `deploy-container-registry.sh`

### Medium Priority
6. `download-db-*.sh` (url, ftp, lagoon)
7. `export-db-image.sh`
8. `provision-sanitize-db.sh`
9. `task-copy-db-acquia.sh`
10. `task-copy-files-acquia.sh`
11. `task-purge-cache-acquia.sh`

### Lower Priority
12. `login.sh`
13. `login-container-registry.sh`
14. `info.sh`
15. `doctor.sh`
16. `update-vortex.sh`

## Conversion Checklist (Per Script)

For each script being converted:

- [ ] Create new file without `.sh` extension
- [ ] Add shebang: `#!/usr/bin/env php`
- [ ] Add: `require_once __DIR__ . '/helpers.php';`
- [ ] Add: `execute_override_if_exists(basename(__FILE__));`
- [ ] Replace bash env loading with `load_env()`
- [ ] Convert color functions: `info()`, `task()`, `pass()`, `fail()`
- [ ] Convert variable assignments and defaults
- [ ] Convert conditionals: `[ ... ]` → `if (...)`
- [ ] Convert loops: `for ... do ... done` → `foreach (...) { ... }`
- [ ] Convert command execution: use `passthru()` or `exec()`
- [ ] Convert string operations
- [ ] Add exit code handling
- [ ] Set execute permission: `chmod +x`
- [ ] Test in isolation
- [ ] Test with overrides
- [ ] Test with real environment variables

## Bash → PHP Conversion Patterns

### Environment Variables
```bash
# Bash
VAR="${VAR:-default}"

# PHP
$var = getenv('VAR') ?: 'default';
```

### Conditionals
```bash
# Bash
if [ -z "${VAR}" ]; then
  echo "empty"
fi

# PHP
if (empty(getenv('VAR'))) {
  echo "empty\n";
}
```

### String Contains
```bash
# Bash
if [ -z "${CHANNELS##*email*}" ]; then
  # contains email
fi

# PHP
if (str_contains($channels, 'email')) {
  // contains email
}
```

### Command Execution
```bash
# Bash
./script.sh "$@"

# PHP
passthru('./script', $exit_code);
```

### Exit Codes
```bash
# Bash
command || exit 1

# PHP
passthru('command', $exit_code);
if ($exit_code !== 0) {
  exit($exit_code);
}
```

## Testing Strategy

### Unit Testing helpers.php
Create tests for:
- Color output functions
- Override system
- Environment loading
- Validation helpers

### Integration Testing
1. Test each script individually with mock env vars
2. Test router scripts (notify, deploy)
3. Test override system
4. Test with real .env files
5. Test in CI environment

### Validation
- All scripts maintain same behavior as bash versions
- Exit codes match
- Output format matches
- Environment variable handling matches

## Package Setup (composer.json)

```json
{
  "name": "drevops/vortex-tooling",
  "description": "Vortex tooling scripts for Drupal projects",
  "type": "library",
  "license": "GPL-2.0-or-later",
  "require": {
    "php": ">=8.1"
  },
  "bin": [
    "notify",
    "notify-slack",
    "notify-email",
    "provision",
    "deploy"
  ],
  "autoload": {
    "files": [
      "helpers.php"
    ]
  }
}
```

## Migration Path for Existing Projects

Projects using bash scripts can migrate gradually:

1. Install composer package: `composer require drevops/vortex-tooling`
2. Scripts available in `vendor/bin/`
3. Update one caller at a time
4. Use override system for custom modifications
5. Remove old bash scripts when all converted

## Success Criteria

- [ ] helpers.php complete with all functions
- [ ] All notify scripts converted and tested
- [ ] High-priority scripts converted
- [ ] Override system working
- [ ] Tests passing
- [ ] Documentation complete
- [ ] Package published to Packagist
- [ ] Migration guide for existing projects

## Timeline Estimate

- **Phase 1 (helpers.php)**: 1 day
- **Phase 2 (notify router)**: 0.5 day
- **Phase 3 (notify-slack)**: 0.5 day
- **Phase 4 (other notify scripts)**: 2 days
- **Phase 5 (remaining scripts)**: 5-10 days
- **Testing & documentation**: 2 days

**Total**: ~2 weeks for complete conversion

## Notes

- Keep both bash and PHP versions during transition
- Maintain backward compatibility
- Document any behavior changes
- Provide examples for common use cases
- Consider adding type hints for PHP 8.1+
- Add PHPStan/Psalm for static analysis
