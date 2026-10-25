<?php

declare(strict_types=1);

namespace DrevOps\VortexTooling\Tests\Unit;

use AlexSkrypnyk\File\File;
use DrevOps\VortexTooling\Tests\Exceptions\QuitErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests for the audit report to SARIF conversion script.
 *
 * Each test converts a report fixture written to a sandbox directory, which is
 * the working directory of the script.
 */
#[Group('utility')]
#[RunTestsInSeparateProcesses]
class ConvertAuditSarifTest extends UnitTestCase {

  /**
   * Sandbox directory the script runs in.
   */
  protected string $project;

  protected function setUp(): void {
    parent::setUp();

    self::envUnsetPrefix('VORTEX_CONVERT_AUDIT_SARIF_');

    $this->project = self::$tmp . '/project_' . uniqid();
    File::mkdir($this->project);
  }

  public function testComposerAdvisory(): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{
      "advisories": {
        "drupal/core": [
          {
            "advisoryId": "PKSA-1111-2222-3333",
            "packageName": "drupal/core",
            "affectedVersions": ">=11.0.0,<11.4.6",
            "title": "Access bypass",
            "cve": "CVE-2026-0001",
            "link": "https://www.drupal.org/sa-core-2026-001",
            "severity": "high"
          }
        ]
      },
      "abandoned": []
    }');

    $output = $this->convert();

    $this->assertStringContainsOrNot($output, [
      '* Started audit report conversion.',
      '* Format:    composer',
      '* Report:    .logs/audit/composer-audit.json',
      '* Lock file: composer.lock',
      '* Converted audit findings to SARIF.',
      '* Findings:  1',
      '* Report:    .logs/audit/composer-audit.sarif',
      '* Finished audit report conversion.',
    ]);

    $this->assertFileExists($this->project . '/.logs/audit/composer-audit.sarif');

    $sarif = $this->readSarif('.logs/audit/composer-audit.sarif');
    $this->assertSame('https://json.schemastore.org/sarif-2.1.0.json', $sarif['$schema']);
    $this->assertSame('2.1.0', $sarif['version']);

    $driver = $sarif['runs'][0]['tool']['driver'];
    $this->assertSame('Composer Audit', $driver['name']);
    $this->assertSame('https://getcomposer.org/doc/03-cli.md#audit', $driver['informationUri']);

    $result = $sarif['runs'][0]['results'][0];
    $this->assertSame('PKSA-1111-2222-3333', $result['ruleId']);
    $this->assertSame('error', $result['level']);
    $this->assertSame('drupal/core: Access bypass (affects >=11.0.0,<11.4.6).', $result['message']['text']);
    $this->assertSame('composer.lock', $result['locations'][0]['physicalLocation']['artifactLocation']['uri']);
    $this->assertSame(1, $result['locations'][0]['physicalLocation']['region']['startLine']);
    $this->assertSame(['vortexAudit/v1' => 'PKSA-1111-2222-3333|drupal/core'], $result['partialFingerprints']);

    $rule = $driver['rules'][0];
    $this->assertSame('PKSA-1111-2222-3333', $rule['id']);
    $this->assertSame('Access bypass', $rule['shortDescription']['text']);
    $this->assertSame('drupal/core is affected by CVE-2026-0001. Affected versions: >=11.0.0,<11.4.6.', $rule['fullDescription']['text']);
    $this->assertSame('drupal/core is affected by CVE-2026-0001. Affected versions: >=11.0.0,<11.4.6. See [CVE-2026-0001](https://www.drupal.org/sa-core-2026-001).', $rule['help']['markdown']);
    $this->assertSame('https://www.drupal.org/sa-core-2026-001', $rule['helpUri']);
    $this->assertSame('error', $rule['defaultConfiguration']['level']);
    $this->assertSame('8.0', $rule['properties']['security-severity']);
    $this->assertSame(['security', 'composer'], $rule['properties']['tags']);
  }

  #[DataProvider('dataProviderSeverity')]
  public function testSeverity(string $severity, string $expected_level, ?string $expected_score): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{
      "advisories": {
        "vendor/thing": [
          {"advisoryId": "PKSA-1-2-3", "packageName": "vendor/thing", "affectedVersions": "<2.0.0", "title": "Issue", "severity": ' . $severity . '}
        ]
      },
      "abandoned": []
    }');

    $this->convert();

    $sarif = $this->readSarif('.logs/audit/composer-audit.sarif');
    $this->assertSame($expected_level, $sarif['runs'][0]['results'][0]['level']);
    $this->assertSame($expected_score, $sarif['runs'][0]['tool']['driver']['rules'][0]['properties']['security-severity'] ?? NULL);
  }

  public static function dataProviderSeverity(): array {
    return [
      'critical' => ['"critical"', 'error', '9.5'],
      'high' => ['"high"', 'error', '8.0'],
      'medium' => ['"medium"', 'warning', '5.5'],
      'moderate' => ['"moderate"', 'warning', '5.5'],
      'low' => ['"low"', 'warning', '3.0'],
      'uppercase' => ['"HIGH"', 'error', '8.0'],
      'info' => ['"info"', 'note', NULL],
      'unknown' => ['"whatever"', 'warning', NULL],
      'null' => ['null', 'warning', NULL],
    ];
  }

  #[DataProvider('dataProviderComposerLink')]
  public function testComposerLink(string $advisory, ?string $expected_help_uri, string $expected_markdown): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{"advisories": {"vendor/thing": [' . $advisory . ']}, "abandoned": []}');

    $this->convert();

    $rule = $this->readSarif('.logs/audit/composer-audit.sarif')['runs'][0]['tool']['driver']['rules'][0];
    $this->assertSame($expected_help_uri, $rule['helpUri'] ?? NULL);
    $this->assertSame($expected_markdown, $rule['help']['markdown']);
  }

  public static function dataProviderComposerLink(): array {
    return [
      'Packagist advisory link' => [
        '{"advisoryId": "PKSA-1-2-3", "packageName": "vendor/thing", "title": "Issue", "cve": "CVE-1", "link": null}',
        'https://packagist.org/security-advisories/PKSA-1-2-3',
        'vendor/thing is affected by CVE-1. Affected versions: *. See [CVE-1](https://packagist.org/security-advisories/PKSA-1-2-3).',
      ],
      'CVE record link' => [
        '{"advisoryId": "internal-1", "packageName": "vendor/thing", "title": "Issue", "cve": "CVE-1"}',
        'https://www.cve.org/CVERecord?id=CVE-1',
        'vendor/thing is affected by CVE-1. Affected versions: *. See [CVE-1](https://www.cve.org/CVERecord?id=CVE-1).',
      ],
      'no link' => [
        '{"advisoryId": "internal-1", "packageName": "vendor/thing", "title": "Issue"}',
        NULL,
        'vendor/thing is affected by internal-1. Affected versions: *.',
      ],
      'missing members use defaults' => [
        '{"advisoryId": 123, "packageName": null}',
        NULL,
        'unknown package is affected by composer-audit/advisory. Affected versions: *.',
      ],
    ];
  }

  public function testAbandoned(): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{
      "advisories": [],
      "abandoned": {"vendor/old": "vendor/new", "vendor/dead": null}
    }');

    $output = $this->convert();

    $this->assertStringContainsString('Findings:  2', $output);

    $sarif = $this->readSarif('.logs/audit/composer-audit.sarif');
    $rules = $sarif['runs'][0]['tool']['driver']['rules'];
    $this->assertCount(1, $rules);
    $this->assertSame('composer-audit/abandoned-package', $rules[0]['id']);
    $this->assertSame('note', $rules[0]['defaultConfiguration']['level']);
    $this->assertSame(['maintainability', 'composer'], $rules[0]['properties']['tags']);

    $results = $sarif['runs'][0]['results'];
    $this->assertSame('note', $results[0]['level']);
    $this->assertSame('vendor/old is abandoned. Use vendor/new instead.', $results[0]['message']['text']);
    $this->assertSame(['vortexAudit/v1' => 'composer-audit/abandoned-package|vendor/old'], $results[0]['partialFingerprints']);
    $this->assertSame('vendor/dead is abandoned and suggests no replacement.', $results[1]['message']['text']);
  }

  public function testPolicies(): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{
      "advisories": [],
      "abandoned": [],
      "filter": {
        "vendor/malicious": [
          {"packageName": "vendor/malicious", "listName": "malware", "constraint": "== 1.0.0", "url": "https://example.com/malware", "reason": "Ships a backdoor.", "id": "MAL-1", "source": "packagist.org"}
        ],
        "vendor/blocked": [
          {"packageName": "vendor/blocked", "listName": "internal", "constraint": "<2.0.0", "url": null, "reason": null, "id": null, "source": null},
          "not-a-policy"
        ],
        "vendor/broken": "not-a-list"
      }
    }');

    $output = $this->convert();

    $this->assertStringContainsString('Findings:  2', $output);

    $sarif = $this->readSarif('.logs/audit/composer-audit.sarif');
    $results = $sarif['runs'][0]['results'];
    $rules = $sarif['runs'][0]['tool']['driver']['rules'];

    $this->assertSame('composer-audit/policy-malware', $results[0]['ruleId']);
    $this->assertSame('error', $results[0]['level']);
    $this->assertSame('vendor/malicious matched the malware dependency policy (== 1.0.0). Ships a backdoor.', $results[0]['message']['text']);
    $this->assertSame(['vortexAudit/v1' => 'composer-audit/policy-malware|vendor/malicious'], $results[0]['partialFingerprints']);
    $this->assertSame('https://example.com/malware', $rules[0]['helpUri']);
    $this->assertSame('Dependency policy: malware', $rules[0]['shortDescription']['text']);
    $this->assertSame('The package version matched the malware dependency policy declared by its repository.', $rules[0]['fullDescription']['text']);

    $this->assertSame('composer-audit/policy-internal', $results[1]['ruleId']);
    $this->assertSame('vendor/blocked matched the internal dependency policy (<2.0.0).', $results[1]['message']['text']);
    $this->assertArrayNotHasKey('helpUri', $rules[1]);
  }

  public function testSkipsIgnoredAndMalformedEntries(): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{
      "advisories": {"vendor/broken": "not-a-list", "vendor/empty": ["not-an-advisory"]},
      "ignored-advisories": {
        "vendor/assessed": [
          {"advisoryId": "PKSA-9-9-9", "packageName": "vendor/assessed", "title": "Dismissed", "ignoreReason": "Not exploitable."}
        ]
      },
      "abandoned": []
    }');

    $output = $this->convert();

    $this->assertStringContainsString('Findings:  0', $output);

    $sarif = $this->readSarif('.logs/audit/composer-audit.sarif');
    $this->assertSame([], $sarif['runs'][0]['results']);
    $this->assertSame([], $sarif['runs'][0]['tool']['driver']['rules']);
  }

  public function testNoFindings(): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{"advisories": [], "abandoned": [], "filter": []}');

    $this->convert();

    $sarif = $this->readSarif('.logs/audit/composer-audit.sarif');
    $this->assertSame('https://json.schemastore.org/sarif-2.1.0.json', $sarif['$schema']);
    $this->assertSame([], $sarif['runs'][0]['results']);
  }

  public function testRepeatedAdvisoryKeepsOneRule(): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{
      "advisories": {
        "vendor/one": [{"advisoryId": "PKSA-1-2-3", "packageName": "vendor/one", "title": "First"}],
        "vendor/two": [{"advisoryId": "PKSA-4-5-6", "packageName": "vendor/two", "title": "Other"}],
        "vendor/three": [{"advisoryId": "PKSA-1-2-3", "packageName": "vendor/three", "title": "Last"}]
      },
      "abandoned": []
    }');

    $this->convert();

    $sarif = $this->readSarif('.logs/audit/composer-audit.sarif');
    $rules = $sarif['runs'][0]['tool']['driver']['rules'];
    $this->assertCount(2, $rules);
    $this->assertSame('PKSA-1-2-3', $rules[0]['id']);
    $this->assertSame('Last', $rules[0]['shortDescription']['text']);
    $this->assertSame('PKSA-4-5-6', $rules[1]['id']);
    $this->assertCount(3, $sarif['runs'][0]['results']);
  }

  public function testComposerLockLines(): void {
    $this->writeFile('composer.lock', implode(PHP_EOL, [
      '{',
      '    "packages": [',
      '        {',
      '            "name": "drupal/core",',
      '            "authors": [',
      '                {',
      '                    "name": "Dries Buytaert"',
      '                }',
      '            ]',
      '        },',
      '        {',
      '            "name": "vendor/thing",',
      '            "replace": {',
      '                "name": "drupal/core",',
      '            }',
      '        }',
      '    ]',
      '}',
    ]) . PHP_EOL);

    $this->writeFile('.logs/audit/composer-audit.json', '{
      "advisories": {
        "vendor/thing": [{"advisoryId": "PKSA-1-2-3", "packageName": "vendor/thing", "title": "Issue"}],
        "vendor/absent": [{"advisoryId": "PKSA-4-5-6", "packageName": "vendor/absent", "title": "Issue"}]
      },
      "abandoned": {"drupal/core": null}
    }');

    $this->convert();

    $results = $this->readSarif('.logs/audit/composer-audit.sarif')['runs'][0]['results'];
    $this->assertSame(12, $results[0]['locations'][0]['physicalLocation']['region']['startLine']);
    $this->assertSame(1, $results[1]['locations'][0]['physicalLocation']['region']['startLine']);
    $this->assertSame(4, $results[2]['locations'][0]['physicalLocation']['region']['startLine']);
  }

  public function testLockFileArgument(): void {
    $this->writeFile('nested/composer.lock', implode(PHP_EOL, [
      '{',
      '    "packages": [',
      '        {',
      '            "name": "vendor/thing",',
      '            "version": "1.0.0"',
      '        }',
      '    ]',
      '}',
    ]) . PHP_EOL);

    $this->writeFile('.logs/audit/composer-audit.json', '{"advisories": {"vendor/thing": [{"advisoryId": "PKSA-1-2-3", "packageName": "vendor/thing", "title": "Issue"}]}, "abandoned": []}');

    $output = $this->convert(['.logs/audit/composer-audit.json', 'composer', 'nested/composer.lock']);

    $this->assertStringContainsString('Lock file: nested/composer.lock', $output);

    $location = $this->readSarif('.logs/audit/composer-audit.sarif')['runs'][0]['results'][0]['locations'][0]['physicalLocation'];
    $this->assertSame('nested/composer.lock', $location['artifactLocation']['uri']);
    $this->assertSame(4, $location['region']['startLine']);
  }

  public function testEnvironmentVariables(): void {
    $this->writeFile('reports/npm.json', '{"vulnerabilities": {"minimist": {"via": [{"title": "Issue", "url": "https://github.com/advisories/GHSA-1", "severity": "high", "range": "*"}]}}}');

    self::envSetMultiple([
      'VORTEX_CONVERT_AUDIT_SARIF_FILE' => 'reports/npm.json',
      'VORTEX_CONVERT_AUDIT_SARIF_FORMAT' => 'npm',
      'VORTEX_CONVERT_AUDIT_SARIF_LOCK_FILE' => 'theme/package-lock.json',
      'VORTEX_CONVERT_AUDIT_SARIF_RESULT_FILE' => 'out/dir/npm.sarif',
    ]);

    $output = $this->convert();

    $this->assertStringContainsOrNot($output, [
      '* Format:    npm',
      '* Report:    reports/npm.json',
      '* Lock file: theme/package-lock.json',
      '* Report:    out/dir/npm.sarif',
    ]);

    $sarif = $this->readSarif('out/dir/npm.sarif');
    $this->assertSame('theme/package-lock.json', $sarif['runs'][0]['results'][0]['locations'][0]['physicalLocation']['artifactLocation']['uri']);
  }

  public function testArgumentsOverrideEnvironmentVariables(): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{"advisories": [], "abandoned": []}');

    self::envSetMultiple([
      'VORTEX_CONVERT_AUDIT_SARIF_FILE' => 'missing.json',
      'VORTEX_CONVERT_AUDIT_SARIF_FORMAT' => 'npm',
    ]);

    $output = $this->convert(['.logs/audit/composer-audit.json', 'composer']);

    $this->assertStringContainsOrNot($output, [
      '* Format:    composer',
      '* Lock file: composer.lock',
      '* Finished audit report conversion.',
    ]);
  }

  public function testNpmReport(): void {
    $this->writeFile('.logs/audit/npm-audit.json', '{
      "auditReportVersion": 2,
      "vulnerabilities": {
        "minimist": {
          "name": "minimist",
          "severity": "critical",
          "via": [
            {"title": "Prototype Pollution", "url": "https://github.com/advisories/GHSA-vh95-rmgr-6w4m", "severity": "moderate", "cvss": {"score": 5.6}, "range": ">=1.0.0 <1.2.3"},
            {"title": "Prototype Pollution", "url": "https://github.com/advisories/GHSA-xvch-5gv4-984h", "severity": "critical", "cvss": {"score": 9.8}, "range": ">=1.0.0 <1.2.6"},
            "another-package"
          ]
        },
        "another-package": {"name": "another-package", "severity": "high", "via": "not-a-list"}
      }
    }');

    $output = $this->convert(['.logs/audit/npm-audit.json', 'npm']);

    $this->assertStringContainsOrNot($output, [
      '* Format:    npm',
      '* Lock file: package-lock.json',
      '* Findings:  2',
    ]);

    $sarif = $this->readSarif('.logs/audit/npm-audit.sarif');
    $driver = $sarif['runs'][0]['tool']['driver'];
    $results = $sarif['runs'][0]['results'];

    $this->assertSame('npm audit', $driver['name']);
    $this->assertSame('https://docs.npmjs.com/cli/commands/npm-audit', $driver['informationUri']);

    $this->assertSame('GHSA-vh95-rmgr-6w4m', $results[0]['ruleId']);
    $this->assertSame('warning', $results[0]['level']);
    $this->assertSame('5.6', $driver['rules'][0]['properties']['security-severity']);
    $this->assertSame(['security', 'npm'], $driver['rules'][0]['properties']['tags']);
    $this->assertSame('https://github.com/advisories/GHSA-vh95-rmgr-6w4m', $driver['rules'][0]['helpUri']);
    $this->assertSame('minimist is affected by GHSA-vh95-rmgr-6w4m. Affected versions: >=1.0.0 <1.2.3.', $driver['rules'][0]['fullDescription']['text']);

    $this->assertSame('GHSA-xvch-5gv4-984h', $results[1]['ruleId']);
    $this->assertSame('error', $results[1]['level']);
    $this->assertSame('9.8', $driver['rules'][1]['properties']['security-severity']);
    $this->assertSame('minimist: Prototype Pollution (affects >=1.0.0 <1.2.6).', $results[1]['message']['text']);
    $this->assertSame(['vortexAudit/v1' => 'GHSA-xvch-5gv4-984h|minimist'], $results[1]['partialFingerprints']);
  }

  #[DataProvider('dataProviderNpmScore')]
  public function testNpmScore(string $advisory, string $expected_id, string $expected_level, ?string $expected_score): void {
    $this->writeFile('.logs/audit/npm-audit.json', '{"vulnerabilities": {"thing": {"name": "thing", "via": [' . $advisory . ']}}}');

    $this->convert(['.logs/audit/npm-audit.json', 'npm']);

    $sarif = $this->readSarif('.logs/audit/npm-audit.sarif');
    $this->assertSame($expected_id, $sarif['runs'][0]['results'][0]['ruleId']);
    $this->assertSame($expected_level, $sarif['runs'][0]['results'][0]['level']);
    $this->assertSame($expected_score, $sarif['runs'][0]['tool']['driver']['rules'][0]['properties']['security-severity'] ?? NULL);
  }

  public static function dataProviderNpmScore(): array {
    return [
      'info severity without a CVSS score' => [
        '{"title": "Informational", "url": "https://github.com/advisories/GHSA-0000-0000-0000", "severity": "info", "range": "*"}',
        'GHSA-0000-0000-0000',
        'note',
        NULL,
      ],
      'severity word without a CVSS score' => [
        '{"title": "Issue", "url": "https://github.com/advisories/GHSA-1", "severity": "high", "cvss": {"score": null}}',
        'GHSA-1',
        'error',
        '8.0',
      ],
      'integer CVSS score' => [
        '{"title": "Issue", "url": "https://github.com/advisories/GHSA-1", "severity": "low", "cvss": {"score": 10}}',
        'GHSA-1',
        'warning',
        '10',
      ],
      'zero CVSS score' => [
        '{"title": "Issue", "url": "https://github.com/advisories/GHSA-1", "severity": "low", "cvss": {"score": 0}}',
        'GHSA-1',
        'warning',
        '0',
      ],
      'advisory without a link' => [
        '{"title": "Issue", "severity": "moderate", "cvss": "not-an-object"}',
        'npm-audit/advisory',
        'warning',
        '5.5',
      ],
    ];
  }

  public function testNpmLockLine(): void {
    $this->writeFile('npm.lock.json', implode(PHP_EOL, [
      '{',
      '    "lockfileVersion": 3,',
      '    "packages": {',
      '        "": {},',
      '        "node_modules/minimist": {',
      '            "version": "1.2.0"',
      '        }',
      '    }',
      '}',
    ]) . PHP_EOL);

    $this->writeFile('.logs/audit/npm-audit.json', '{
      "auditReportVersion": 2,
      "vulnerabilities": {
        "minimist": {"name": "minimist", "severity": "critical", "via": [{"title": "Issue", "url": "https://github.com/advisories/GHSA-1", "severity": "critical", "range": "*"}]}
      }
    }');

    $this->convert(['.logs/audit/npm-audit.json', 'npm', 'npm.lock.json']);

    $location = $this->readSarif('.logs/audit/npm-audit.sarif')['runs'][0]['results'][0]['locations'][0]['physicalLocation'];
    $this->assertSame('npm.lock.json', $location['artifactLocation']['uri']);
    $this->assertSame(5, $location['region']['startLine']);
  }

  #[DataProvider('dataProviderFailure')]
  public function testFailure(?string $report, array $args, array $expected): void {
    if ($report !== NULL) {
      $this->writeFile('.logs/audit/composer-audit.json', $report);
    }

    try {
      $this->convert($args, 1);
    }
    catch (QuitErrorException $e) {
      $this->assertStringContainsOrNot($e->getOutput(), $expected);
      throw $e;
    }
  }

  public static function dataProviderFailure(): array {
    return [
      'unknown report format' => [
        '{}',
        ['.logs/audit/composer-audit.json', 'yarn'],
        [
          "* Unknown report format yarn. Use 'composer' or 'npm'.",
          '! Finished audit report conversion.',
        ],
      ],
      'missing report' => [
        NULL,
        [],
        ['* Audit report .logs/audit/composer-audit.json does not exist.'],
      ],
      'invalid JSON' => [
        'not json',
        [],
        ['* Audit report .logs/audit/composer-audit.json is not valid JSON.'],
      ],
      'null JSON' => [
        'null',
        [],
        ['* Audit report .logs/audit/composer-audit.json is not valid JSON.'],
      ],
      'report that is not an object' => [
        '["not", "an", "object"]',
        [],
        [
          '* Unable to convert .logs/audit/composer-audit.json.',
          '! Finished audit report conversion.',
        ],
      ],
    ];
  }

  public function testUnwritableResult(): void {
    $this->writeFile('.logs/audit/composer-audit.json', '{"advisories": [], "abandoned": []}');
    $this->writeFile('blocked', 'A file where the result directory would be.');

    self::envSet('VORTEX_CONVERT_AUDIT_SARIF_RESULT_FILE', 'blocked/composer-audit.sarif');

    try {
      $this->convert([], 1);
    }
    catch (QuitErrorException $e) {
      $this->assertStringContainsOrNot($e->getOutput(), [
        '* Unable to convert .logs/audit/composer-audit.json.',
        '! Finished audit report conversion.',
      ]);
      throw $e;
    }
  }

  /**
   * Run the script in the sandbox directory with positional arguments.
   */
  protected function convert(array $args = [], ?int $early_exit_code = NULL): string {
    $GLOBALS['argv'] = array_merge(['vortex-convert-audit-sarif'], $args);

    return $this->runScript('src/vortex-convert-audit-sarif', $early_exit_code, $this->project);
  }

  /**
   * Write a file relative to the sandbox directory.
   */
  protected function writeFile(string $path, string $content): void {
    File::dump($this->project . '/' . $path, $content);
  }

  /**
   * Read a SARIF report relative to the sandbox directory.
   */
  protected function readSarif(string $path): array {
    $sarif = json_decode(File::read($this->project . '/' . $path), TRUE);

    if (!is_array($sarif)) {
      throw new \RuntimeException('Unable to read SARIF report ' . $path);
    }

    return $sarif;
  }

}
