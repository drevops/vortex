<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use DrevOps\VortexInstaller\Utils\Yaml;

#[CoversClass(Yaml::class)]
class YamlTest extends UnitTestCase {

  #[DataProvider('dataProviderValidateFile')]
  public function testValidateFile(string $yaml_content, string $expected_exception_message = ''): void {
    if ($expected_exception_message !== '' && $expected_exception_message !== '0') {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expected_exception_message);
    }

    $temp_file = tempnam(sys_get_temp_dir(), 'yaml_test_');
    file_put_contents($temp_file, $yaml_content);

    Yaml::validateFile($temp_file);

    if ($expected_exception_message === '' || $expected_exception_message === '0') {
      $this->addToAssertionCount(1);
    }
  }

  public function testValidateFileNonExistent(): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('File does not exist or is not readable');

    $non_existent_file = sys_get_temp_dir() . '/non_existent_file.yml';
    Yaml::validateFile($non_existent_file);
  }

  public static function dataProviderValidateFile(): array {
    return [
      'valid YAML file' => [
        <<<YAML
key: value
list:
  - item1
  - item2
YAML
      ],
      'invalid YAML syntax' => [
        <<<YAML
key: value
invalid_yaml: [
  missing_closing_bracket
YAML, 'Malformed inline YAML string',
      ],
    ];
  }

  #[DataProvider('dataProviderValidate')]
  public function testValidate(string $content, string $expected_exception_message = ''): void {
    if ($expected_exception_message !== '' && $expected_exception_message !== '0') {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage($expected_exception_message);
    }

    Yaml::validate($content);

    if ($expected_exception_message === '' || $expected_exception_message === '0') {
      $this->addToAssertionCount(1);
    }
  }

  public static function dataProviderValidate(): array {
    return [
      'valid simple YAML' => [
        <<<YAML
key: value
YAML,
      ],
      'valid complex YAML' => [
        <<<YAML
users:
  - name: john
    email: john@example.com
  - name: jane
    email: jane@example.com
config:
  debug: true
  port: 8080
YAML,
      ],
      'valid empty YAML' => [''],
      'valid YAML with arrays' => [
        <<<YAML
items:
  - item1
  - item2
  - item3
YAML,
      ],
      'valid YAML with nested objects' => [
        <<<YAML
database:
  host: localhost
  port: 3306
  credentials:
    username: user
    password: pass
YAML,
      ],
      'invalid YAML missing closing bracket' => [
        <<<YAML
list: [
  item1,
  item2
YAML, 'Malformed inline YAML string',
      ],
      'invalid YAML unclosed quotes' => ['key: "unclosed string', 'Malformed inline YAML string'],
      'invalid YAML syntax' => [
        <<<YAML
key: value
!!invalid
YAML, 'Unable to parse',
      ],
      'invalid YAML tabs mixed with spaces' => [
        <<<'YAML'
key:
	value1
  value2
YAML, 'A YAML file cannot contain tabs',
      ],
    ];
  }

  #[DataProvider('dataProviderCollapseEmptyLinesInLiteralBlock')]
  public function testCollapseEmptyLinesInLiteralBlock(string $input, string $expected): void {
    $result = Yaml::collapseEmptyLinesInLiteralBlock($input);
    $this->assertEquals($expected, $result);
  }

  public static function dataProviderCollapseEmptyLinesInLiteralBlock(): array {
    return [
      'no literal blocks' => [
        <<<YAML
key: value
other: data
YAML,
        <<<YAML
key: value
other: data
YAML,
      ],

      'single empty line' => [
        <<<YAML
  cmd: |
    echo "hello"

    echo "world"
YAML,
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
      ],

      'multiple empty lines' => [
        <<<YAML
  cmd: |
    echo "hello"



    echo "world"
YAML,
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
      ],

      'multiple empty lines start and end' => [
        <<<YAML
  cmd: |


    echo "hello"



    echo "world"


YAML,
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
      ],

      'multiple blocks with empty lines' => [
        <<<YAML
  step1:
    op: |
      echo "first"


      echo "command"
  step2:
    op: |
      echo "second"



      echo "command"
YAML,
        <<<YAML
  step1:
    op: |
      echo "first"
      echo "command"
  step2:
    op: |
      echo "second"
      echo "command"
YAML,
      ],

      'multiple blocks with empty lines with line between' => [
        <<<YAML
  step1:
    cmd: |
      echo "first"


      echo "command"

  step2:
    cmd: |
      echo "second"



      echo "command"
YAML,
        <<<YAML
  step1:
    cmd: |
      echo "first"
      echo "command"

  step2:
    cmd: |
      echo "second"
      echo "command"
YAML,
      ],

      'multiple blocks with empty lines with multiple lines between' => [
        <<<YAML
  step1:
    cmd: |
      echo "first"


      echo "command"



  step2:
    cmd: |
      echo "second"



      echo "command"
YAML,
        <<<YAML
  step1:
    cmd: |
      echo "first"
      echo "command"



  step2:
    cmd: |
      echo "second"
      echo "command"
YAML,
      ],

      'multiple blocks with empty lines with multiple lines between and same level' => [
        <<<YAML
  step1:
    cmd: |
      echo "first"


      echo "command"
    op: |


      echo "second command"

      echo "third command"




  step2:
    cmd: |
      echo "second"



      echo "command"
YAML,
        <<<YAML
  step1:
    cmd: |
      echo "first"
      echo "command"
    op: |
      echo "second command"
      echo "third command"




  step2:
    cmd: |
      echo "second"
      echo "command"
YAML,
      ],

      'no empty lines' => [
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
      ],

      'end of document with empty lines' => [
        <<<YAML
  cmd: |
    echo "hello"



    echo "world"
YAML,
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
      ],

      'full document' => [
        <<<YAML
  lint-be:
    usage: Lint back-end code.
    cmd: |
      ahoy cli vendor/bin/phpcs

      ahoy cli vendor/bin/rector --clear-cache --dry-run
      ahoy cli vendor/bin/phpmd . text phpmd.xml

  lint-fe:
    usage: Lint front-end code.
    cmd: |
      ahoy cli vendor/bin/twig-cs-fixer lint
      ahoy cli "yarn run lint"
      #;< DRUPAL_THEME
      ahoy cli "yarn run --cwd=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint"
      #;> DRUPAL_THEME

  lint-tests:
    usage: Lint tests code.
    cmd: |
      ahoy cli vendor/bin/gherkinlint lint tests/behat/features
YAML,
        <<<YAML
  lint-be:
    usage: Lint back-end code.
    cmd: |
      ahoy cli vendor/bin/phpcs
      ahoy cli vendor/bin/rector --clear-cache --dry-run
      ahoy cli vendor/bin/phpmd . text phpmd.xml

  lint-fe:
    usage: Lint front-end code.
    cmd: |
      ahoy cli vendor/bin/twig-cs-fixer lint
      ahoy cli "yarn run lint"
      #;< DRUPAL_THEME
      ahoy cli "yarn run --cwd=\${WEBROOT}/themes/custom/\${DRUPAL_THEME} lint"
      #;> DRUPAL_THEME

  lint-tests:
    usage: Lint tests code.
    cmd: |
      ahoy cli vendor/bin/gherkinlint lint tests/behat/features
YAML,
      ],
    ];
  }

  #[DataProvider('dataProviderCollapseFirstEmptyLinesInLiteralBlock')]
  public function testCollapseFirstEmptyLinesInLiteralBlock(string $input, string $expected): void {
    $result = Yaml::collapseFirstEmptyLinesInLiteralBlock($input);
    $this->assertEquals($expected, $result);
  }

  public static function dataProviderCollapseFirstEmptyLinesInLiteralBlock(): array {
    return [
      'no empty lines' => [
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
      ],

      '1 empty line at start' => [
        <<<YAML
  cmd: |

    echo "hello"
    echo "world"
YAML,
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
      ],

      '2 empty lines at start' => [
        <<<YAML
  cmd: |


    echo "hello"
    echo "world"
YAML,
        <<<YAML
  cmd: |
    echo "hello"
    echo "world"
YAML,
      ],

      '2 empty lines at start and 2 in middle' => [
        <<<YAML
  cmd: |


    echo "hello"


    echo "world"
YAML,
        <<<YAML
  cmd: |
    echo "hello"


    echo "world"
YAML,
      ],
    ];
  }

}
