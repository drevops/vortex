#!/usr/bin/env php
<?php

/**
 * @file
 * Vortex Customizer - interactive TUI prototype (throwaway spike).
 *
 * A self-contained, panel-style "control panel" for the Vortex customizer.
 * No dependencies - it draws every screen itself with ANSI colour so the
 * visuals are fully under our control. It writes NO files; Apply is a stub.
 *
 * It takes over the whole terminal (alternate screen buffer). Because that
 * disables the terminal's own scrollback, the panel scrolls internally: a
 * pinned header + footer with a scrollable body that follows the cursor and
 * shows ▲/▼ indicators when there is more above or below.
 *
 * Run interactively:
 *   php .vortex/customizer/playground/run.php
 *
 * Options:
 *   --demo        Print a static storyboard of key frames and exit.
 *   --no-color    Disable ANSI colour (useful for alignment inspection).
 *   --update      Simulate "update existing project" mode (shows `auto` badges).
 *
 * Keys: up/down move - pgup/pgdn/home/end jump - enter open/edit - esc back -
 *       a apply - q quit.
 */

declare(strict_types=1);

// -----------------------------------------------------------------------------
// Layout + colour primitives.
// -----------------------------------------------------------------------------

const COLS = 78;
const IND = '  ';

$GLOBALS['color'] = TRUE;

function paint(string $s, string $codes): string {
  if (!$GLOBALS['color'] || $s === '') {
    return $s;
  }
  // Re-open our style after any embedded reset so nested styles survive.
  $s = str_replace("\033[0m", "\033[0m\033[" . $codes . 'm', $s);
  return "\033[" . $codes . 'm' . $s . "\033[0m";
}

function cyan(string $s): string { return paint($s, '36'); }
function green(string $s): string { return paint($s, '32'); }
function yellow(string $s): string { return paint($s, '33'); }
function magenta(string $s): string { return paint($s, '35'); }
function blue(string $s): string { return paint($s, '34'); }
function red(string $s): string { return paint($s, '31'); }
function grey(string $s): string { return paint($s, '90'); }
function bold(string $s): string { return paint($s, '1'); }
function dim(string $s): string { return paint($s, '2'); }
function reverse(string $s): string { return paint($s, '7'); }
function boldcyan(string $s): string { return paint($s, '1;36'); }
function boldgreen(string $s): string { return paint($s, '1;32'); }

/**
 * Visible length - ignores ANSI SGR sequences and counts display columns.
 */
function vlen(string $s): int {
  $s = preg_replace('/\033\[[0-9;]*m/', '', $s);
  return mb_strwidth($s);
}

function pad_right(string $s, int $w): string {
  $len = vlen($s);
  return $len < $w ? $s . str_repeat(' ', $w - $len) : $s;
}

/**
 * Truncate a plain (un-coloured) string to a display width, adding an ellipsis.
 */
function clip(string $s, int $w): string {
  if (mb_strwidth($s) <= $w) {
    return $s;
  }
  $out = '';
  $used = 0;
  foreach (preg_split('//u', $s, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
    $cw = mb_strwidth($ch);
    if ($used + $cw > $w - 1) {
      break;
    }
    $out .= $ch;
    $used += $cw;
  }
  return $out . '…';
}

/**
 * A left segment and a right segment on one line, right-aligned to COLS.
 */
function spread(string $left, string $right, int $cols = COLS): string {
  $gap = $cols - vlen($left) - vlen($right);
  $gap = max(1, $gap);
  return $left . str_repeat(' ', $gap) . $right;
}

function rule(): string {
  return ' ' . dim(str_repeat('─', COLS - 1));
}

/**
 * A divider with a right-aligned indicator, e.g. "▲ 3 more" / "▼ 8 more".
 */
function scroll_rule(string $right): string {
  $w = COLS - 1;
  $tag = ' ' . $right . ' ';
  $dash = str_repeat('─', max(0, $w - mb_strwidth($tag)));
  return ' ' . dim($dash) . cyan($tag);
}

// -----------------------------------------------------------------------------
// Data model - the 12 sections and their fields (seeded, mirrors the handlers).
// -----------------------------------------------------------------------------

/**
 * Build the section registry. Each field: id, label, desc, type, options,
 * default, and an optional `when` predicate for conditional visibility.
 */
function build_sections(): array {
  $modules = ['admin_toolbar', 'coffee', 'config_split', 'config_update', 'devel', 'drupal_helpers', 'environment_indicator', 'generated_content', 'pathauto', 'redirect', 'reroute_email', 'robotstxt', 'sdc_devel', 'seckit', 'shield', 'stage_file_proxy', 'testmode', 'xmlsitemap'];
  $tools = ['phpcs', 'phpstan', 'rector', 'eslint', 'stylelint', 'phpunit', 'behat', 'jest'];
  $tz = ['UTC', 'Europe/London', 'Europe/Berlin', 'Europe/Paris', 'Europe/Madrid', 'Europe/Kyiv', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles', 'America/Sao_Paulo', 'Asia/Dubai', 'Asia/Kolkata', 'Asia/Singapore', 'Asia/Tokyo', 'Australia/Sydney', 'Australia/Melbourne', 'Pacific/Auckland'];

  $keyed = fn(array $list): array => array_combine($list, array_map(fn($x) => [$x, ''], $list));

  return [
    ['id' => 'general', 'title' => 'General information', 'desc' => 'Site name, organization and public domain', 'fields' => [
      ['id' => 'name', 'label' => 'Site name', 'desc' => 'Human-readable name of your site.', 'type' => 'text', 'default' => 'Acme', 'required' => TRUE],
      ['id' => 'machine_name', 'label' => 'Site machine name', 'desc' => 'Machine-readable name: lowercase letters, numbers and underscores.', 'type' => 'text', 'default' => 'acme', 'machine' => TRUE],
      ['id' => 'org', 'label' => 'Organization name', 'desc' => 'Human-readable name of your organization.', 'type' => 'text', 'default' => 'Acme Org'],
      ['id' => 'org_machine_name', 'label' => 'Organization machine name', 'desc' => 'Machine-readable name of your organization.', 'type' => 'text', 'default' => 'acme_org', 'machine' => TRUE],
      ['id' => 'domain', 'label' => 'Public domain', 'desc' => 'Primary public domain the site will be served on.', 'type' => 'text', 'default' => 'acme.com'],
    ]],
    ['id' => 'drupal', 'title' => 'Drupal', 'desc' => 'Install profile, modules, theme and front-end build', 'fields' => [
      ['id' => 'starter', 'label' => 'Starter', 'desc' => 'How the site is first created on the initial build.', 'type' => 'select', 'default' => 'load_demodb', 'options' => [
        'install_profile_core' => ['Drupal, installed from profile', 'Install a standard or custom profile from scratch.'],
        'install_profile_drupalcms' => ['Drupal CMS', 'Install the Drupal CMS distribution.'],
        'load_demodb' => ['Drupal, from the demo database', 'Start from the bundled demo database.'],
      ]],
      ['id' => 'profile', 'label' => 'Profile', 'desc' => 'Drupal installation profile the site is built on.', 'type' => 'select', 'default' => 'standard', 'options' => [
        'standard' => ['Standard', 'Drupal standard profile.'],
        'minimal' => ['Minimal', 'Minimal profile, few modules enabled.'],
        'demo_umami' => ['Demo: Umami', 'Umami demonstration profile.'],
        'custom' => ['Custom…', 'Provide your own profile machine name.'],
      ]],
      ['id' => 'profile_custom', 'label' => 'Custom profile machine name', 'desc' => 'Machine name of your custom installation profile.', 'type' => 'text', 'default' => '', 'machine' => TRUE, 'when' => fn($a) => ($a['profile'] ?? '') === 'custom'],
      ['id' => 'modules', 'label' => 'Modules', 'desc' => 'Optional contributed modules to include.', 'type' => 'multiselect', 'default' => $modules, 'options' => $keyed($modules)],
      ['id' => 'module_prefix', 'label' => 'Custom module prefix', 'desc' => 'Prefix used for custom module machine names.', 'type' => 'text', 'default' => 'acme', 'machine' => TRUE],
      ['id' => 'custom_modules', 'label' => 'Custom modules', 'desc' => 'Which scaffolded custom modules to keep.', 'type' => 'multiselect', 'default' => ['base', 'search', 'demo'], 'options' => [
        'base' => ['base', 'Core custom module with shared code.'],
        'search' => ['search', 'Search API + Solr integration.'],
        'demo' => ['demo', 'Demo content and examples.'],
      ]],
      ['id' => 'theme', 'label' => 'Theme', 'desc' => "Base theme for the site's front-end.", 'type' => 'select', 'default' => 'olivero', 'options' => [
        'olivero' => ['Olivero', 'Modern default Drupal front-end theme.'],
        'claro' => ['Claro', 'Clean administration theme.'],
        'stark' => ['Stark', 'Unstyled base theme.'],
        'custom' => ['Custom…', 'Provide your own theme machine name.'],
      ]],
      ['id' => 'theme_custom', 'label' => 'Custom theme machine name', 'desc' => 'Machine name of your custom theme.', 'type' => 'text', 'default' => '', 'machine' => TRUE, 'when' => fn($a) => ($a['theme'] ?? '') === 'custom'],
      ['id' => 'frontend_build', 'label' => 'Build front-end assets in the container?', 'desc' => 'Compile the theme assets inside the container image.', 'type' => 'confirm', 'default' => TRUE, 'when' => fn($a) => ($a['theme'] ?? '') === 'custom'],
    ]],
    ['id' => 'code', 'title' => 'Code repository', 'desc' => 'Where the code lives and how releases are versioned', 'fields' => [
      ['id' => 'code_provider', 'label' => 'Repository provider', 'desc' => 'Where the source code is hosted.', 'type' => 'select', 'default' => 'github', 'options' => ['github' => ['GitHub', ''], 'other' => ['Other', '']]],
      ['id' => 'version_scheme', 'label' => 'Release versioning scheme', 'desc' => 'How releases are numbered.', 'type' => 'select', 'default' => 'calver', 'options' => [
        'calver' => ['CalVer', 'Calendar-based versioning, e.g. 2024.5.1.'],
        'semver' => ['SemVer', 'Semantic versioning, e.g. 1.4.2.'],
        'other' => ['Other', ''],
      ]],
    ]],
    ['id' => 'environment', 'title' => 'Environment', 'desc' => 'Timezone, Docker services and developer tooling', 'fields' => [
      ['id' => 'timezone', 'label' => 'Timezone', 'desc' => 'Default timezone for the site and containers.', 'type' => 'suggest', 'default' => 'UTC', 'options' => $keyed($tz)],
      ['id' => 'services', 'label' => 'Services', 'desc' => 'Optional containerized services.', 'type' => 'multiselect', 'default' => ['clamav', 'redis', 'solr'], 'options' => [
        'clamav' => ['clamav', 'Antivirus scanning for uploads.'],
        'solr' => ['solr', 'Apache Solr search backend.'],
        'redis' => ['redis', 'Redis cache backend.'],
      ]],
      ['id' => 'tools', 'label' => 'Development tools', 'desc' => 'Linters and test runners to include.', 'type' => 'multiselect', 'default' => $tools, 'options' => $keyed($tools)],
    ]],
    ['id' => 'hosting', 'title' => 'Hosting', 'desc' => 'Target hosting provider and project name', 'fields' => [
      ['id' => 'hosting_provider', 'label' => 'Hosting provider', 'desc' => 'Where the site is hosted.', 'type' => 'select', 'default' => 'none', 'auto' => TRUE, 'options' => [
        'acquia' => ['Acquia', 'Acquia Cloud hosting.'],
        'lagoon' => ['Lagoon', 'amazee.io Lagoon hosting.'],
        'other' => ['Other', ''],
        'none' => ['None', ''],
      ]],
      ['id' => 'hosting_project_name', 'label' => 'Hosting project name', 'desc' => 'Project/application name on the hosting provider.', 'type' => 'text', 'default' => '', 'when' => fn($a) => in_array($a['hosting_provider'] ?? '', ['acquia', 'lagoon'], TRUE)],
      ['id' => 'webroot', 'label' => 'Custom web root directory', 'desc' => 'Directory that serves as the Drupal web root.', 'type' => 'text', 'default' => 'web', 'auto' => TRUE],
    ]],
    ['id' => 'deployment', 'title' => 'Deployment', 'desc' => 'How code is shipped to the hosting environment', 'fields' => [
      ['id' => 'deploy_types', 'label' => 'Deployment types', 'desc' => 'One or more deployment mechanisms.', 'type' => 'multiselect', 'default' => ['webhook'], 'options' => [
        'artifact' => ['artifact', 'Build and push a deployment artifact.'],
        'lagoon' => ['lagoon', 'Deploy via Lagoon.'],
        'webhook' => ['webhook', 'Trigger a deployment webhook.'],
      ]],
    ]],
    ['id' => 'workflow', 'title' => 'Workflow', 'desc' => 'Provisioning method and database source', 'fields' => [
      ['id' => 'provision_type', 'label' => 'Provision type', 'desc' => 'How the site database is provisioned.', 'type' => 'select', 'default' => 'database', 'options' => [
        'database' => ['Database', 'Provision from a database dump.'],
        'profile' => ['Profile', 'Install from the Drupal profile.'],
      ]],
      ['id' => 'database_fetch_source', 'label' => 'Database source', 'desc' => 'Where the database dump is fetched from.', 'type' => 'select', 'default' => 'url', 'when' => fn($a) => ($a['provision_type'] ?? '') !== 'profile', 'options' => [
        'url' => ['URL', 'Download from a URL.'], 'ftp' => ['FTP', 'Fetch over FTP.'], 'acquia' => ['Acquia Cloud', ''], 'lagoon' => ['Lagoon', ''], 'container_registry' => ['Container registry', 'Pull a database-in-image.'], 's3' => ['S3', ''], 'none' => ['None', ''],
      ]],
      ['id' => 'database_image', 'label' => 'Database container image', 'desc' => 'Image name and tag of the database-in-image.', 'type' => 'text', 'default' => 'acme/acme-data:latest', 'when' => fn($a) => ($a['provision_type'] ?? '') !== 'profile' && ($a['database_fetch_source'] ?? '') === 'container_registry'],
      ['id' => 'migration', 'label' => 'Use a second database for migrations?', 'desc' => 'Add a migration source database service.', 'type' => 'confirm', 'default' => FALSE],
      ['id' => 'migration_fetch_source', 'label' => 'Migration database source', 'desc' => 'Where the migration database is fetched from.', 'type' => 'select', 'default' => 'url', 'when' => fn($a) => !empty($a['migration']), 'options' => [
        'url' => ['URL', ''], 'ftp' => ['FTP', ''], 'acquia' => ['Acquia Cloud', ''], 'lagoon' => ['Lagoon', ''], 'container_registry' => ['Container registry', ''], 's3' => ['S3', ''],
      ]],
      ['id' => 'migration_image', 'label' => 'Migration container image', 'desc' => 'Image name and tag of the migration database.', 'type' => 'text', 'default' => 'acme/acme-data-migration:latest', 'when' => fn($a) => !empty($a['migration']) && ($a['migration_fetch_source'] ?? '') === 'container_registry'],
    ]],
    ['id' => 'notifications', 'title' => 'Notifications', 'desc' => 'Where build and deployment notifications are sent', 'fields' => [
      ['id' => 'notification_channels', 'label' => 'Notification channels', 'desc' => 'One or more notification destinations.', 'type' => 'multiselect', 'default' => ['email'], 'options' => [
        'email' => ['email', ''], 'github' => ['github', ''], 'jira' => ['jira', ''], 'newrelic' => ['newrelic', ''], 'slack' => ['slack', ''], 'webhook' => ['webhook', ''],
      ]],
    ]],
    ['id' => 'ci', 'title' => 'Continuous integration', 'desc' => 'CI provider and visual regression testing', 'fields' => [
      ['id' => 'ci_provider', 'label' => 'CI provider', 'desc' => 'Continuous integration platform.', 'type' => 'select', 'default' => 'gha', 'options' => [
        'gha' => ['GitHub Actions', ''], 'circleci' => ['CircleCI', ''], 'none' => ['None', ''],
      ]],
      ['id' => 'visual_regression', 'label' => 'Visual regression testing with Diffy?', 'desc' => 'Run automated visual regression on deploys.', 'type' => 'confirm', 'default' => FALSE],
    ]],
    ['id' => 'automations', 'title' => 'Automations', 'desc' => 'Dependency updates, coverage and PR automation', 'fields' => [
      ['id' => 'dependency_updates_provider', 'label' => 'Dependency updates provider', 'desc' => 'How dependencies are kept up to date.', 'type' => 'select', 'default' => 'renovatebot_app', 'options' => [
        'renovatebot_app' => ['RenovateBot (app)', 'Hosted RenovateBot GitHub app.'], 'renovatebot_ci' => ['RenovateBot (CI)', 'Self-hosted in CI.'], 'none' => ['None', ''],
      ]],
      ['id' => 'code_coverage_provider', 'label' => 'Code coverage provider', 'desc' => 'Where coverage reports are uploaded.', 'type' => 'select', 'default' => 'none', 'options' => ['codecov' => ['Codecov', ''], 'none' => ['None', '']]],
      ['id' => 'assign_author_pr', 'label' => 'Auto-assign the author to their PR?', 'desc' => 'Assign the opener as the PR assignee.', 'type' => 'confirm', 'default' => TRUE],
      ['id' => 'label_merge_conflicts_pr', 'label' => 'Auto-label PRs with merge conflicts?', 'desc' => 'Add a CONFLICT label when conflicts occur.', 'type' => 'confirm', 'default' => TRUE],
    ]],
    ['id' => 'documentation', 'title' => 'Documentation', 'desc' => 'Whether project documentation is kept', 'fields' => [
      ['id' => 'preserve_docs_project', 'label' => 'Preserve project documentation?', 'desc' => 'Keep the docs/ directory in the project.', 'type' => 'confirm', 'default' => TRUE],
    ]],
    ['id' => 'ai', 'title' => 'AI', 'desc' => 'Whether AI agent instructions are included', 'fields' => [
      ['id' => 'ai_code_instructions', 'label' => 'Provide AI agent instructions?', 'desc' => 'Include AGENTS.md / CLAUDE.md guidance.', 'type' => 'confirm', 'default' => TRUE],
    ]],
  ];
}

// -----------------------------------------------------------------------------
// The application.
// -----------------------------------------------------------------------------

class Customizer {

  protected array $sections;
  protected array $answers = [];
  protected array $defaults = [];
  protected bool $update = FALSE;

  protected string $screen = 'hub';
  protected int $hubIndex = 0;
  protected int $sectionIndex = 0;
  protected int $fieldIndex = 0;

  /** @var array<string,mixed> Transient editor state. */
  protected array $editor = [];

  protected int $rows = 40;
  protected int $scroll = 0;
  protected string $scrollKey = '';

  protected $in;
  protected string $buf = '';
  protected int $bufPos = 0;
  protected bool $scripted = FALSE;
  protected string $sttyRestore = '';

  public function __construct(bool $update = FALSE) {
    $this->sections = build_sections();
    $this->update = $update;
    foreach ($this->sections as $section) {
      foreach ($section['fields'] as $field) {
        $this->answers[$field['id']] = $field['default'];
        $this->defaults[$field['id']] = $field['default'];
      }
    }
  }

  // ---- Field / value helpers ------------------------------------------------

  protected function field(string $id): ?array {
    foreach ($this->sections as $section) {
      foreach ($section['fields'] as $field) {
        if ($field['id'] === $id) {
          return $field;
        }
      }
    }
    return NULL;
  }

  protected function isActive(array $field): bool {
    return empty($field['when']) || ($field['when'])($this->answers);
  }

  protected function isEdited(array $field): bool {
    return $this->answers[$field['id']] != $this->defaults[$field['id']];
  }

  protected function isAuto(array $field): bool {
    return $this->update && !empty($field['auto']) && !$this->isEdited($field);
  }

  protected function optLabel(array $field, string $key): string {
    $opt = $field['options'][$key] ?? NULL;
    if (is_array($opt)) {
      return $opt[0];
    }
    return $opt ?? $key;
  }

  protected function optDesc(array $field, string $key): string {
    $opt = $field['options'][$key] ?? NULL;
    return is_array($opt) ? ($opt[1] ?? '') : '';
  }

  /**
   * A compact human display of a field's current value.
   */
  protected function display(array $field): string {
    $v = $this->answers[$field['id']];
    switch ($field['type']) {
      case 'text':
        return ($v === '' || $v === NULL) ? '—' : (string) $v;

      case 'confirm':
        return $v ? 'yes' : 'no';

      case 'select':
      case 'suggest':
        return $this->optLabel($field, (string) $v);

      case 'multiselect':
        $arr = array_values((array) $v);
        if (!$arr) {
          return 'none';
        }
        if (count($arr) <= 3) {
          return implode(', ', array_map(fn($k) => $this->optLabel($field, $k), $arr));
        }
        return count($arr) . ' selected';
    }
    return (string) $v;
  }

  /**
   * One-line summary of a section's active field values for the hub.
   */
  protected function summary(array $section): string {
    $parts = [];
    foreach ($section['fields'] as $field) {
      if ($this->isActive($field)) {
        $parts[] = $this->display($field);
      }
      if (count($parts) >= 4) {
        break;
      }
    }
    return implode(' · ', $parts);
  }

  protected function activeFields(array $section): array {
    return array_values(array_filter($section['fields'], fn($f) => $this->isActive($f)));
  }

  protected function sectionEdited(array $section): bool {
    foreach ($section['fields'] as $field) {
      if ($this->isActive($field) && $this->isEdited($field)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  protected function sectionAuto(array $section): bool {
    foreach ($section['fields'] as $field) {
      if ($this->isActive($field) && $this->isAuto($field)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * A cursor + title line with an optional right-aligned badge (no trailing pad).
   */
  protected function titleRow(string $marker, string $title, string $badge): string {
    return $badge === '' ? IND . $marker . $title : IND . spread($marker . $title, $badge . ' ');
  }

  protected function conditionText(array $field): string {
    return match ($field['id']) {
      'profile_custom' => 'appears when Profile = Custom',
      'theme_custom', 'frontend_build' => 'appears when Theme = Custom',
      'hosting_project_name' => 'appears when Hosting = Acquia or Lagoon',
      'database_fetch_source' => 'appears when Provision type = Database',
      'database_image' => 'appears when Database source = Container registry',
      'migration_fetch_source' => 'appears when the migration database is enabled',
      'migration_image' => 'appears when Migration source = Container registry',
      default => 'appears when its dependency is met',
    };
  }

  // ---- Frames ---------------------------------------------------------------
  //
  // Every screen returns a frame: a pinned `header`, a scrollable `body`, a
  // pinned `footer`, and a `focus` line index within the body to keep visible
  // (-1 = no cursor; the user scrolls manually). paint() slices the body to the
  // terminal height. The last header line and first footer line are always
  // dividers, so paint() can stamp the ▲/▼ scroll indicators onto them.

  protected function frame(): array {
    return match ($this->screen) {
      'section' => $this->renderSection(),
      'editor' => $this->renderEditor(),
      'review' => $this->renderReview(),
      default => $this->renderHub(),
    };
  }

  public function renderHub(): array {
    $header = [
      '',
      IND . spread(bold('Vortex Customizer') . dim('  ·  configure ') . cyan('"Acme"'), dim('↑/↓  ·  ↵ open  ·  a apply  ·  q quit')),
      rule(),
    ];

    $body = [];
    $focus = -1;
    foreach ($this->sections as $i => $section) {
      $sel = $this->screen === 'hub' && $this->hubIndex === $i;
      if ($sel) {
        $focus = count($body);
      }
      $marker = $sel ? boldcyan('❯ ') : '  ';
      $title = $sel ? boldcyan($section['title']) : $section['title'];
      $badge = $this->sectionEdited($section) ? yellow('✎') : ($this->sectionAuto($section) ? dim('auto') : '');
      $body[] = $this->titleRow($marker, $title, $badge);
      $body[] = IND . '    ' . grey(clip($section['desc'], COLS - 6));
      $value = clip($this->summary($section), COLS - 6);
      $body[] = IND . '    ' . ($sel ? $value : dim($value));
    }

    $count = count($this->sections);
    $selReview = $this->screen === 'hub' && $this->hubIndex === $count;
    if ($selReview) {
      $focus = count($body) - 1;
    }
    $marker = $selReview ? boldcyan('❯ ') : '  ';
    $label = $selReview ? boldgreen('Review & apply') : green('Review & apply');
    $footer = [
      rule(),
      IND . spread($marker . boldgreen('✔') . ' ' . $label, dim('nothing written yet')),
    ];

    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  public function renderSection(): array {
    $section = $this->sections[$this->sectionIndex];
    $header = [
      '',
      IND . spread(bold($section['title']), dim('section ' . ($this->sectionIndex + 1) . ' of ' . count($this->sections))),
      rule(),
    ];

    $body = [];
    $focus = -1;
    $nav = $this->activeFields($section);
    foreach ($section['fields'] as $field) {
      if (!$this->isActive($field)) {
        $plain = '⌁ ' . $field['label'] . '   inactive · ' . $this->conditionText($field);
        $body[] = IND . '  ' . grey(clip($plain, COLS - 4));
        continue;
      }
      $pos = array_search($field['id'], array_column($nav, 'id'), TRUE);
      $sel = $this->screen === 'section' && $this->fieldIndex === $pos;
      if ($sel) {
        $focus = count($body);
      }
      $marker = $sel ? boldcyan('❯ ') : '  ';
      $label = $sel ? boldcyan($field['label']) : $field['label'];
      $badge = $this->isEdited($field) ? yellow('✎') : ($this->isAuto($field) ? dim('auto') : '');
      $body[] = $this->titleRow($marker, $label, $badge);
      $body[] = IND . '    ' . grey(clip($field['desc'], COLS - 6));
      $value = clip($this->display($field), COLS - 6);
      $body[] = IND . '    ' . ($sel ? cyan($value) : dim($value));
    }

    $footer = [rule(), IND . dim('↑/↓ move  ·  ↵ edit  ·  esc back  ·  r reset section')];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  public function renderEditor(): array {
    $field = $this->editor['field'];
    return match ($field['type']) {
      'select' => $this->renderChoice($field, FALSE),
      'suggest' => $this->renderChoice($field, TRUE),
      'multiselect' => $this->renderMulti($field),
      'confirm' => $this->renderConfirm($field),
      default => $this->renderText($field),
    };
  }

  /**
   * Pinned editor header: blank, "Section › Field" (+ optional right tag), rule.
   */
  protected function editorHeader(array $field, string $right = ''): array {
    $section = $this->sections[$this->sectionIndex];
    $title = bold($section['title']) . dim(' › ') . boldcyan($field['label']);
    return ['', $right === '' ? IND . $title : IND . spread($title, dim($right)), rule()];
  }

  protected function renderChoice(array $field, bool $suggest): array {
    $header = $this->editorHeader($field);
    $body = [IND . grey($field['desc']), ''];

    $keys = array_keys($field['options']);
    if ($suggest) {
      $filter = (string) $this->editor['filter'];
      $keys = array_values(array_filter($keys, fn($k) => $filter === '' || stripos($k, $filter) !== FALSE));
      $body[] = IND . dim('filter: ') . ($filter === '' ? dim('(type to filter)') : cyan($filter)) . cyan('▏');
      $body[] = '';
    }

    $focus = -1;
    if (!$keys) {
      $body[] = IND . '  ' . dim('no matches');
    }
    $cursor = min((int) $this->editor['cursor'], max(0, count($keys) - 1));
    foreach ($keys as $idx => $key) {
      $sel = $idx === $cursor;
      if ($sel) {
        $focus = count($body);
      }
      $on = $this->editor['value'] === $key;
      $radio = $on ? boldgreen('(•)') : dim('( )');
      $label = $sel ? boldcyan($this->optLabel($field, $key)) : $this->optLabel($field, $key);
      $desc = $this->optDesc($field, $key);
      $line = $desc === '' ? $radio . ' ' . $label : $radio . ' ' . pad_right($label, 20) . ' ' . grey($desc);
      $body[] = IND . ($sel ? boldcyan('❯ ') : '  ') . $line;
    }

    $hint = $suggest ? 'type filter  ·  ↑/↓ move  ·  ↵ select  ·  esc cancel' : '↑/↓ move  ·  ↵ select  ·  esc cancel';
    $footer = [rule(), IND . dim($hint)];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  protected function renderMulti(array $field): array {
    $all = array_keys($field['options']);
    $filter = (string) $this->editor['filter'];
    $keys = array_values(array_filter($all, fn($k) => $filter === '' || stripos($k, $filter) !== FALSE));
    $selected = (array) $this->editor['value'];
    $count = count(array_intersect($all, $selected));

    $header = $this->editorHeader($field, $count . ' of ' . count($all) . ' selected');
    $body = [IND . grey($field['desc']), ''];
    if ($filter !== '') {
      $body[] = IND . dim('filter: ') . cyan($filter) . cyan('▏');
      $body[] = '';
    }

    $cursor = min((int) $this->editor['cursor'], max(0, count($keys) - 1));
    $focus = -1;
    foreach ($keys as $idx => $key) {
      $sel = $idx === $cursor;
      if ($sel) {
        $focus = count($body);
      }
      $on = in_array($key, $selected, TRUE);
      $box = $on ? boldgreen('[x]') : dim('[ ]');
      $label = $sel ? boldcyan($this->optLabel($field, $key)) : $this->optLabel($field, $key);
      $desc = $this->optDesc($field, $key);
      $line = $desc === '' ? $box . ' ' . $label : $box . ' ' . pad_right($label, 22) . ' ' . grey(clip($desc, COLS - 34));
      $body[] = IND . ($sel ? boldcyan('❯ ') : '  ') . $line;
    }

    $footer = [rule(), IND . dim('space toggle  ·  type to filter  ·  ↑/↓ move  ·  ↵ confirm  ·  esc cancel')];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  protected function renderConfirm(array $field): array {
    $header = $this->editorHeader($field);
    $body = [IND . grey($field['desc']), ''];
    $focus = -1;
    foreach ([1 => 'Yes', 0 => 'No'] as $flag => $label) {
      $val = (bool) $flag;
      $sel = $this->editor['value'] === $val;
      if ($sel) {
        $focus = count($body);
      }
      $radio = $sel ? boldgreen('(•)') : dim('( )');
      $text = $sel ? boldcyan($label) : $label;
      $body[] = IND . ($sel ? boldcyan('❯ ') : '  ') . $radio . ' ' . $text;
    }
    $footer = [rule(), IND . dim('↑/↓ or y/n  ·  ↵ confirm  ·  esc cancel')];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => $focus];
  }

  protected function renderText(array $field): array {
    $header = $this->editorHeader($field);
    $text = (string) $this->editor['value'];
    $inner = COLS - 8;
    $body = [
      IND . grey($field['desc']),
      '',
      IND . '  ' . dim('┌' . str_repeat('─', $inner) . '┐'),
      IND . '  ' . dim('│') . ' ' . pad_right(cyan($text) . reverse(' '), $inner - 2) . ' ' . dim('│'),
      IND . '  ' . dim('└' . str_repeat('─', $inner) . '┘'),
      '',
    ];
    $err = $this->editor['error'] ?? '';
    $body[] = $err !== '' ? IND . '  ' . red('✕ ' . $err) : IND . '  ' . green('✔ Looks good.');
    $footer = [rule(), IND . dim('type to edit  ·  ↵ save  ·  esc cancel')];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => 0];
  }

  public function renderReview(): array {
    $header = ['', IND . bold('Review & apply'), rule()];
    $body = [];
    foreach ($this->sections as $section) {
      $body[] = IND . boldcyan($section['title']);
      foreach ($section['fields'] as $field) {
        if (!$this->isActive($field)) {
          continue;
        }
        $badge = $this->isEdited($field) ? '  ' . yellow('✎') : '';
        $body[] = IND . '  ' . pad_right($field['label'] . '  ', 32) . dim($this->display($field)) . $badge;
      }
    }
    $footer = [
      rule(),
      IND . dim('↑/↓ scroll  ·  ') . reverse(boldgreen(' ↵ apply ')) . dim('  ·  esc back  ·  q quit'),
    ];
    return ['header' => $header, 'body' => $body, 'footer' => $footer, 'focus' => -1];
  }

  protected function frameToString(array $f): string {
    return implode("\n", array_merge($f['header'], $f['body'], $f['footer']));
  }

  // ---- Paint (viewport + scrolling) -----------------------------------------

  protected function paint(): void {
    $this->detectSize();
    echo "\033[2J\033[H" . $this->composeFrame();
  }

  protected function composeFrame(): string {
    $f = $this->frame();
    $key = $this->screen . ':' . $this->sectionIndex . ':' . ($this->editor['field']['id'] ?? '');
    if ($key !== $this->scrollKey) {
      $this->scrollKey = $key;
      $this->scroll = 0;
    }

    $header = $f['header'];
    $footer = $f['footer'];
    $body = $f['body'];
    $avail = max(1, $this->rows - count($header) - count($footer));
    $total = count($body);

    $offset = $this->scroll;
    if ($f['focus'] >= 0) {
      if ($f['focus'] < $offset) {
        $offset = $f['focus'];
      }
      if ($f['focus'] >= $offset + $avail) {
        $offset = $f['focus'] - $avail + 1;
      }
    }
    $offset = max(0, min($offset, max(0, $total - $avail)));
    $this->scroll = $offset;

    $view = array_slice($body, $offset, $avail);
    if ($total > $avail) {
      while (count($view) < $avail) {
        $view[] = '';
      }
      if ($offset > 0) {
        $header[count($header) - 1] = scroll_rule('▲ ' . $offset . ' more');
      }
      $below = $total - $offset - $avail;
      if ($below > 0) {
        $footer[0] = scroll_rule('▼ ' . $below . ' more');
      }
    }

    return implode("\n", array_merge($header, $view, $footer));
  }

  /**
   * Headless render of one screen at a fixed height (for verifying scrolling).
   */
  public function probe(int $rows, string $screen, int $index): void {
    $this->scripted = TRUE;
    $this->rows = $rows;
    $this->screen = $screen;
    $this->hubIndex = $index;
    $this->sectionIndex = min($index, count($this->sections) - 1);
    echo $this->composeFrame() . "\n";
  }

  protected function detectSize(): void {
    if ($this->scripted) {
      return;
    }
    $out = trim((string) @shell_exec('stty size </dev/tty 2>/dev/null'));
    if (preg_match('/^(\d+)\s+(\d+)$/', $out, $m)) {
      $this->rows = max(8, (int) $m[1]);
    }
  }

  // ---- Input ----------------------------------------------------------------

  protected function key(): string {
    return $this->scripted ? $this->keyBuffered() : $this->keyTty();
  }

  protected function keyTty(): string {
    $c = fread($this->in, 1);
    if ($c === '' || $c === FALSE) {
      return 'EOF';
    }
    if (ord($c) === 0x1b) {
      stream_set_blocking($this->in, FALSE);
      $seq = '';
      for ($i = 0; $i < 6; $i++) {
        $n = fread($this->in, 1);
        if ($n === '' || $n === FALSE) {
          usleep(1200);
          $n = fread($this->in, 1);
        }
        if ($n === '' || $n === FALSE) {
          break;
        }
        $seq .= $n;
        if (ctype_alpha($n) || $n === '~') {
          break;
        }
      }
      stream_set_blocking($this->in, TRUE);
      return $this->csi($seq);
    }
    return $this->normalize(ord($c), $c);
  }

  protected function keyBuffered(): string {
    if ($this->bufPos >= strlen($this->buf)) {
      return 'EOF';
    }
    $c = $this->buf[$this->bufPos++];
    if (ord($c) === 0x1b) {
      $seq = '';
      while ($this->bufPos < strlen($this->buf) && strlen($seq) < 6) {
        $n = $this->buf[$this->bufPos++];
        $seq .= $n;
        if (ctype_alpha($n) || $n === '~') {
          break;
        }
      }
      return $this->csi($seq);
    }
    return $this->normalize(ord($c), $c);
  }

  /**
   * Map a CSI escape tail to a token. Empty tail = a lone ESC (back).
   */
  protected function csi(string $seq): string {
    if ($seq === '') {
      return 'ESC';
    }
    if ($seq[0] === '[' || $seq[0] === 'O') {
      return match (substr($seq, 1)) {
        'A' => 'UP', 'B' => 'DOWN', 'C' => 'RIGHT', 'D' => 'LEFT',
        '5~' => 'PGUP', '6~' => 'PGDN',
        'H', '1~', '7~' => 'HOME',
        'F', '4~', '8~' => 'END',
        default => 'ESC',
      };
    }
    return 'ESC';
  }

  protected function normalize(int $o, string $c): string {
    return match (TRUE) {
      $o === 3, $o === 4 => 'CTRL_C',
      $o === 13, $o === 10 => 'ENTER',
      $o === 127, $o === 8 => 'BACKSPACE',
      $o === 32 => 'SPACE',
      default => $c,
    };
  }

  // ---- Main loop ------------------------------------------------------------

  public function run(): int {
    $this->scripted = !@stream_isatty(STDIN);
    if ($this->scripted) {
      $this->buf = stream_get_contents(STDIN) ?: '';
      return $this->runScripted();
    }

    $this->in = STDIN;
    $this->sttyRestore = trim((string) shell_exec('stty -g 2>/dev/null'));
    shell_exec('stty -icanon -echo -isig min 1 time 0 2>/dev/null');
    register_shutdown_function([$this, 'restore']);
    echo "\033[?1049h\033[?25l";

    while (TRUE) {
      $this->paint();
      $k = $this->key();
      if ($k === 'CTRL_C' || $k === 'EOF') {
        break;
      }
      if ($this->dispatch($k) === FALSE) {
        break;
      }
    }
    $this->restore();
    echo "Bye.\n";
    return 0;
  }

  protected function runScripted(): int {
    while ($this->bufPos < strlen($this->buf)) {
      $k = $this->key();
      if ($k === 'CTRL_C' || $k === 'EOF') {
        break;
      }
      if ($this->dispatch($k) === FALSE) {
        break;
      }
    }
    $this->dumpAnswers();
    return 0;
  }

  /**
   * Headless driver for verification: comma-separated tokens, e.g.
   *   down,enter,type:Acme Digital,enter
   * Tokens: up down left right enter esc space back pgup pgdn home end,
   * `type:<text>`, or a single char.
   */
  public function runKeys(string $spec): int {
    $map = ['up' => 'UP', 'down' => 'DOWN', 'left' => 'LEFT', 'right' => 'RIGHT', 'enter' => 'ENTER', 'esc' => 'ESC', 'space' => 'SPACE', 'back' => 'BACKSPACE', 'pgup' => 'PGUP', 'pgdn' => 'PGDN', 'home' => 'HOME', 'end' => 'END'];
    foreach (explode(',', $spec) as $token) {
      if ($token === '') {
        continue;
      }
      if (str_starts_with($token, 'type:')) {
        foreach (preg_split('//u', substr($token, 5), -1, PREG_SPLIT_NO_EMPTY) as $ch) {
          $this->dispatch($ch === ' ' ? 'SPACE' : $ch);
        }
        continue;
      }
      if ($this->dispatch($map[$token] ?? $token) === FALSE) {
        break;
      }
    }
    $this->dumpAnswers();
    return 0;
  }

  protected function dumpAnswers(): void {
    fwrite(STDOUT, "HEADLESS RUN - final answers:\n");
    foreach ($this->sections as $section) {
      foreach ($section['fields'] as $field) {
        $v = $this->answers[$field['id']];
        $v = is_array($v) ? implode(',', $v) : var_export($v, TRUE);
        $edited = $this->isEdited($field) ? ' [edited]' : '';
        fwrite(STDOUT, sprintf("  %-28s %s%s\n", $field['id'], $v, $edited));
      }
    }
  }

  public function restore(): void {
    if ($this->scripted) {
      return;
    }
    echo "\033[?25h\033[?1049l";
    if ($this->sttyRestore !== '') {
      shell_exec('stty ' . $this->sttyRestore . ' 2>/dev/null');
    }
    else {
      shell_exec('stty sane 2>/dev/null');
    }
  }

  // ---- Dispatch -------------------------------------------------------------

  protected function dispatch(string $k): bool {
    return match ($this->screen) {
      'hub' => $this->onHub($k),
      'section' => $this->onSection($k),
      'editor' => $this->onEditor($k),
      'review' => $this->onReview($k),
      default => TRUE,
    };
  }

  protected function onHub(string $k): bool {
    $max = count($this->sections);
    switch ($k) {
      case 'UP':
        $this->hubIndex = ($this->hubIndex - 1 + ($max + 1)) % ($max + 1);
        break;

      case 'DOWN':
        $this->hubIndex = ($this->hubIndex + 1) % ($max + 1);
        break;

      case 'PGUP':
        $this->hubIndex = max(0, $this->hubIndex - 5);
        break;

      case 'PGDN':
        $this->hubIndex = min($max, $this->hubIndex + 5);
        break;

      case 'HOME':
        $this->hubIndex = 0;
        break;

      case 'END':
        $this->hubIndex = $max;
        break;

      case 'ENTER':
      case 'RIGHT':
        if ($this->hubIndex === $max) {
          $this->screen = 'review';
        }
        else {
          $this->sectionIndex = $this->hubIndex;
          $this->fieldIndex = 0;
          $this->screen = 'section';
        }
        break;

      case 'a':
        $this->screen = 'review';
        break;

      case 'q':
        return FALSE;
    }
    return TRUE;
  }

  protected function onSection(string $k): bool {
    $section = $this->sections[$this->sectionIndex];
    $nav = $this->activeFields($section);
    $count = count($nav);
    switch ($k) {
      case 'UP':
        $this->fieldIndex = ($this->fieldIndex - 1 + $count) % $count;
        break;

      case 'DOWN':
        $this->fieldIndex = ($this->fieldIndex + 1) % $count;
        break;

      case 'PGUP':
      case 'HOME':
        $this->fieldIndex = 0;
        break;

      case 'PGDN':
      case 'END':
        $this->fieldIndex = $count - 1;
        break;

      case 'ESC':
      case 'LEFT':
        $this->screen = 'hub';
        $this->hubIndex = $this->sectionIndex;
        break;

      case 'ENTER':
      case 'RIGHT':
        $this->openEditor($nav[$this->fieldIndex]);
        break;

      case 'r':
        foreach ($section['fields'] as $field) {
          $this->answers[$field['id']] = $this->defaults[$field['id']];
        }
        break;

      case 'q':
        return FALSE;
    }
    return TRUE;
  }

  protected function openEditor(array $field): void {
    $this->screen = 'editor';
    $this->editor = [
      'field' => $field,
      'value' => $this->answers[$field['id']],
      'cursor' => 0,
      'filter' => '',
      'error' => '',
    ];
    if (in_array($field['type'], ['select', 'suggest'], TRUE)) {
      $keys = array_keys($field['options']);
      $pos = array_search($this->answers[$field['id']], $keys, TRUE);
      $this->editor['cursor'] = $pos === FALSE ? 0 : $pos;
    }
    if ($field['type'] === 'multiselect') {
      $this->editor['value'] = array_values((array) $this->answers[$field['id']]);
    }
  }

  protected function closeEditor(bool $save): void {
    if ($save) {
      $field = $this->editor['field'];
      $this->answers[$field['id']] = $this->editor['value'];
    }
    $this->screen = 'section';
    // Re-sync field index in case conditionals changed the active set.
    $nav = $this->activeFields($this->sections[$this->sectionIndex]);
    $this->fieldIndex = min($this->fieldIndex, max(0, count($nav) - 1));
  }

  protected function onEditor(string $k): bool {
    $field = $this->editor['field'];
    if ($k === 'CTRL_C') {
      return FALSE;
    }
    return match ($field['type']) {
      'select' => $this->onChoice($k, FALSE),
      'suggest' => $this->onChoice($k, TRUE),
      'multiselect' => $this->onMulti($k),
      'confirm' => $this->onConfirm($k),
      default => $this->onText($k),
    };
  }

  protected function visibleKeys(array $field, bool $filtered): array {
    $keys = array_keys($field['options']);
    if ($filtered) {
      $filter = (string) $this->editor['filter'];
      $keys = array_values(array_filter($keys, fn($k) => $filter === '' || stripos($k, $filter) !== FALSE));
    }
    return $keys;
  }

  protected function onChoice(string $k, bool $suggest): bool {
    $field = $this->editor['field'];
    $keys = $this->visibleKeys($field, $suggest);
    $n = max(1, count($keys));
    switch ($k) {
      case 'UP':
        $this->editor['cursor'] = ($this->editor['cursor'] - 1 + $n) % $n;
        break;

      case 'DOWN':
        $this->editor['cursor'] = ($this->editor['cursor'] + 1) % $n;
        break;

      case 'HOME':
      case 'PGUP':
        $this->editor['cursor'] = 0;
        break;

      case 'END':
      case 'PGDN':
        $this->editor['cursor'] = $n - 1;
        break;

      case 'ENTER':
        if ($keys) {
          $this->editor['value'] = $keys[$this->editor['cursor']] ?? $this->editor['value'];
          $this->closeEditor(TRUE);
        }
        break;

      case 'ESC':
        $this->closeEditor(FALSE);
        break;

      case 'BACKSPACE':
        if ($suggest) {
          $this->editor['filter'] = mb_substr((string) $this->editor['filter'], 0, -1);
          $this->editor['cursor'] = 0;
        }
        break;

      default:
        if ($suggest && strlen($k) === 1 && ctype_print($k)) {
          $this->editor['filter'] .= $k;
          $this->editor['cursor'] = 0;
        }
    }
    return TRUE;
  }

  protected function onMulti(string $k): bool {
    $field = $this->editor['field'];
    $keys = $this->visibleKeys($field, TRUE);
    $n = max(1, count($keys));
    $cursor = &$this->editor['cursor'];
    $cursor = min($cursor, $n - 1);
    switch ($k) {
      case 'UP':
        $cursor = ($cursor - 1 + $n) % $n;
        break;

      case 'DOWN':
        $cursor = ($cursor + 1) % $n;
        break;

      case 'HOME':
      case 'PGUP':
        $cursor = 0;
        break;

      case 'END':
      case 'PGDN':
        $cursor = $n - 1;
        break;

      case 'SPACE':
        if ($keys) {
          $selected_key = $keys[$cursor];
          $val = (array) $this->editor['value'];
          if (in_array($selected_key, $val, TRUE)) {
            $val = array_values(array_diff($val, [$selected_key]));
          }
          else {
            $val[] = $selected_key;
          }
          $this->editor['value'] = $val;
        }
        break;

      case 'ENTER':
        $this->closeEditor(TRUE);
        break;

      case 'ESC':
        $this->closeEditor(FALSE);
        break;

      case 'BACKSPACE':
        $this->editor['filter'] = mb_substr((string) $this->editor['filter'], 0, -1);
        $cursor = 0;
        break;

      default:
        if (strlen($k) === 1 && ctype_print($k) && $k !== ' ') {
          $this->editor['filter'] .= $k;
          $cursor = 0;
        }
    }
    return TRUE;
  }

  protected function onConfirm(string $k): bool {
    switch ($k) {
      case 'UP':
      case 'DOWN':
      case 'LEFT':
      case 'RIGHT':
        $this->editor['value'] = !$this->editor['value'];
        break;

      case 'y':
        $this->editor['value'] = TRUE;
        break;

      case 'n':
        $this->editor['value'] = FALSE;
        break;

      case 'ENTER':
        $this->closeEditor(TRUE);
        break;

      case 'ESC':
        $this->closeEditor(FALSE);
        break;
    }
    return TRUE;
  }

  protected function onText(string $k): bool {
    $field = $this->editor['field'];
    switch ($k) {
      case 'ENTER':
        $err = $this->validate($field, (string) $this->editor['value']);
        if ($err === '') {
          $this->closeEditor(TRUE);
        }
        else {
          $this->editor['error'] = $err;
        }
        break;

      case 'ESC':
        $this->closeEditor(FALSE);
        break;

      case 'BACKSPACE':
        $this->editor['value'] = mb_substr((string) $this->editor['value'], 0, -1);
        $this->editor['error'] = '';
        break;

      default:
        if ($k === 'SPACE') {
          $k = ' ';
        }
        if (strlen($k) === 1 && ctype_print($k)) {
          $this->editor['value'] .= $k;
          $this->editor['error'] = '';
        }
    }
    return TRUE;
  }

  protected function validate(array $field, string $value): string {
    if (!empty($field['required']) && trim($value) === '') {
      return $field['label'] . ' is required.';
    }
    if (!empty($field['machine']) && $value !== '' && !preg_match('/^[a-z][a-z0-9_]*$/', $value)) {
      return 'Use lowercase letters, numbers and underscores; must start with a letter.';
    }
    return '';
  }

  protected function onReview(string $k): bool {
    $page = max(1, $this->rows - 4);
    switch ($k) {
      case 'UP':
        $this->scroll = max(0, $this->scroll - 1);
        break;

      case 'DOWN':
        $this->scroll++;
        break;

      case 'PGUP':
        $this->scroll = max(0, $this->scroll - $page);
        break;

      case 'PGDN':
        $this->scroll += $page;
        break;

      case 'HOME':
        $this->scroll = 0;
        break;

      case 'END':
        $this->scroll = 100000;
        break;

      case 'ENTER':
        $this->paint();
        echo "\n" . IND . green('✔ (prototype) Apply is a stub - no files were changed.') . "\n";
        return FALSE;

      case 'ESC':
      case 'LEFT':
        $this->screen = 'hub';
        break;

      case 'q':
        return FALSE;
    }
    return TRUE;
  }

  // ---- Demo storyboard ------------------------------------------------------

  public function demo(): void {
    $frames = [];

    $this->screen = 'hub';
    $this->hubIndex = 0;
    $frames['The control panel (hub)'] = $this->frameToString($this->renderHub());

    $this->sectionIndex = 1;
    $this->fieldIndex = 1;
    $this->screen = 'section';
    $frames['A section opened (Drupal)'] = $this->frameToString($this->renderSection());

    $this->screen = 'editor';
    $this->openEditorDemo('profile', 1);
    $frames['Select field (Profile)'] = $this->frameToString($this->renderEditor());

    $this->openEditorDemo('modules', 6);
    $frames['Multiselect field (Modules)'] = $this->frameToString($this->renderEditor());

    $this->openEditorDemo('name', 0);
    $frames['Text field (Site name)'] = $this->frameToString($this->renderEditor());

    $this->openEditorDemo('name', 0);
    $this->editor['value'] = '';
    $this->editor['error'] = 'Site name is required.';
    $frames['Text field with a validation error'] = $this->frameToString($this->renderEditor());

    $this->openEditorDemo('frontend_build', 0);
    $frames['Confirm field'] = $this->frameToString($this->renderConfirm($this->editor['field']));

    $this->answers['theme'] = 'olivero';
    $this->answers['name'] = 'Acme Digital';
    $this->screen = 'review';
    $frames['Review & apply'] = $this->frameToString($this->renderReview());

    foreach ($frames as $caption => $frame) {
      echo "\n" . IND . magenta('◆ ' . $caption) . "\n";
      echo $frame . "\n";
    }
  }

  protected function openEditorDemo(string $id, int $cursor): void {
    $this->sectionIndex = $this->sectionOf($id);
    $field = $this->field($id);
    $this->editor = ['field' => $field, 'value' => $this->answers[$id], 'cursor' => $cursor, 'filter' => '', 'error' => ''];
    if ($field['type'] === 'multiselect') {
      $this->editor['value'] = array_values((array) $this->answers[$id]);
    }
  }

  protected function sectionOf(string $id): int {
    foreach ($this->sections as $i => $section) {
      foreach ($section['fields'] as $field) {
        if ($field['id'] === $id) {
          return $i;
        }
      }
    }
    return 0;
  }

}

// -----------------------------------------------------------------------------
// Entry point.
// -----------------------------------------------------------------------------

$args = array_slice($argv, 1);
if (in_array('--no-color', $args, TRUE) || getenv('NO_COLOR')) {
  $GLOBALS['color'] = FALSE;
}
$app = new Customizer(in_array('--update', $args, TRUE));

foreach ($args as $a) {
  if (str_starts_with($a, '--keys=')) {
    exit($app->runKeys(substr($a, 7)));
  }
  if (str_starts_with($a, '--probe=')) {
    [$r, $s, $i] = array_pad(explode(',', substr($a, 8)), 3, '');
    $app->probe((int) $r ?: 18, $s !== '' ? $s : 'hub', (int) $i);
    exit(0);
  }
}

if (in_array('--demo', $args, TRUE)) {
  $app->demo();
  exit(0);
}

exit($app->run());
