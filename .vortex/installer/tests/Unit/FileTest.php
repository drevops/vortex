<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Tests\Unit;

use DrevOps\VortexInstaller\Utils\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

#[CoversClass(File::class)]
class FileTest extends UnitTestCase {

  #[DataProvider('dataProviderIsInternal')]
  public function testIsInternal(string $path, bool $expected): void {
    $result = File::isInternal($path);
    $this->assertSame($expected, $result);
  }

  public static function dataProviderIsInternal(): array {
    return [
      'exact match - LICENSE' => ['/LICENSE', TRUE],
      'exact match - CODE_OF_CONDUCT.md' => ['/CODE_OF_CONDUCT.md', TRUE],
      'exact match - CONTRIBUTING.md' => ['/CONTRIBUTING.md', TRUE],
      'exact match - SECURITY.md' => ['/SECURITY.md', TRUE],
      'directory match - docs' => ['/.vortex/docs', TRUE],
      'directory match - tests' => ['/.vortex/tests', TRUE],
      'relative path stripped' => ['./LICENSE', TRUE],
      'relative path in subdir' => ['docs/LICENSE', FALSE],
      'non-internal path' => ['/some/other/path', FALSE],
      'hidden file not matched' => ['/.gitignore', FALSE],
    ];
  }

  #[DataProvider('dataProviderCollapseYamlEmptyLinesInLiteralBlocks')]
  public function testCollapseYamlEmptyLinesInLiteralBlocks(string $input, string $expected): void {
    $actual = File::collapseYamlEmptyLinesInLiteralBlocks($input);
    $this->assertSame($expected, $actual);
  }

  public static function dataProviderCollapseYamlEmptyLinesInLiteralBlocks(): array {
    return [
      'empty string' => [
        '',
        '',
      ],
      'no literal blocks' => [
        <<<YAML
        key: value
        another: test
        YAML,
        <<<YAML
        key: value
        another: test
        YAML,
      ],
      'literal block immediately after pipe' => [
        <<<YAML
        |


        content
        YAML,
        <<<YAML
        |
        content
        YAML,
      ],
      'literal block with multiple empty lines after pipe' => [
        <<<YAML
        |



        content
        YAML,
        <<<YAML
        |
        content
        YAML,
      ],
      'literal block with whitespace in empty lines' => [
        <<<YAML
        |


        content
        YAML,
        <<<YAML
        |
        content
        YAML,
      ],
      'multiple literal blocks with collapsible lines' => [
        <<<YAML
        first: |


        content1
        second: |


        content2
        YAML,
        <<<YAML
        first: |
        content1
        second: |
        content2
        YAML,
      ],
      'mixed content no effect on non-literal blocks' => [
        <<<YAML
        key: value


        description: |


        content
        another: test
        YAML,
        <<<YAML
        key: value


        description: |
        content
        another: test
        YAML,
      ],
      'literal block with no empty lines to collapse' => [
        <<<YAML
        |
        line1
        line2
        line3
        YAML,
        <<<YAML
        |
        line1
        line2
        line3
        YAML,
      ],
      'literal block with single empty line after pipe' => [
        <<<YAML
        |

        content
        YAML,
        <<<YAML
        |
        content
        YAML,
      ],
    ];
  }

}
