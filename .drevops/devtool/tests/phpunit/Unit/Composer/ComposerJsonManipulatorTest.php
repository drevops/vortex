<?php

namespace DrevOps\DevTool\Tests\Unit\Composer;

use Composer\Json\JsonFile;
use DrevOps\DevTool\Composer\ComposerJsonManipulator;
use DrevOps\DevTool\Tests\Traits\VfsTrait;
use Helmich\JsonAssert\JsonAssertions;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DrevOps\DevTool\Composer\ComposerJsonManipulator
 */
class ComposerJsonManipulatorTest extends TestCase {

  use JsonAssertions;
  use VfsTrait;

  /**
   * Path to the test JSON file.
   */
  protected string $testJsonFilePath;

  protected function setUp(): void {
    $this->testJsonFilePath = $this->vfsCreateFile('/test.json')->url();
  }

  /**
   * @covers ::save
   * @covers ::getFormattedData
   * @covers ::getNewline
   */
  public function testSave(): void {
    $m = new ComposerJsonManipulator(json_encode((object) [
      'string' => 'value',
      'int' => 1,
      'float' => 1.1,
      'array' => ['a', 'b', 'c'],
      'object' => (object) ['a' => 'b'],
    ]));

    $m->save($this->testJsonFilePath);
    $actual = JsonFile::parseJson(file_get_contents($this->testJsonFilePath));

    $this->assertJsonValueEquals($actual, '$.string', 'value');
    $this->assertJsonValueEquals($actual, '$.int', 1);
    $this->assertJsonValueEquals($actual, '$.float', 1.1);
    $this->assertJsonValueEquals($actual, '$.array', ['a', 'b', 'c']);
    $this->assertJsonValueEquals($actual, '$.object.a', 'b');
  }

  /**
   * @covers ::addRepository
   * @dataProvider dataProviderAddRepository
   */
  public function testAddRepository(array $structure, array $additions, mixed $expected, bool $append = TRUE): void {
    $m = new ComposerJsonManipulator(json_encode((object) $structure));

    foreach ($additions as $name => $config) {
      $m->addRepository($name, $config, $append);
    }

    $this->assertEquals($expected, $m->getFormattedData());
  }

  public static function dataProviderAddRepository(): array {
    return [

      [[], [], []],
      [
        [],
        ['name' => ['type' => 'vcs', 'url' => 'https://example.com/repo.git']],
        ['repositories' => [['type' => 'vcs', 'url' => 'https://example.com/repo.git']]],
      ],

      [
        [],
        [
          'repo1' => ['type' => 'git', 'url' => 'https://example.com/repo1.git'],
          'repo2' => ['type' => 'composer', 'url' => 'https://example.com/repo2'],
        ],
        [
          'repositories' => [
            ['type' => 'git', 'url' => 'https://example.com/repo1.git'],
            ['type' => 'composer', 'url' => 'https://example.com/repo2'],
          ],
        ],
      ],

      [
        ['repositories' => [['type' => 'package', 'url' => 'https://example.com/oldrepo']]],
        ['new_repo' => ['type' => 'vcs', 'url' => 'https://example.com/newrepo.git']],
        [
          'repositories' => [
            ['type' => 'package', 'url' => 'https://example.com/oldrepo'],
            ['type' => 'vcs', 'url' => 'https://example.com/newrepo.git'],
          ],
        ],
      ],

      // Rewrite instead of append.
      [
        ['repositories' => [['type' => 'package', 'url' => 'https://example.com/oldrepo']]],
        ['new_repo' => ['type' => 'vcs', 'url' => 'https://example.com/newrepo.git']],
        [
          'repositories' => [
            ['type' => 'vcs', 'url' => 'https://example.com/newrepo.git'],
          ],
        ],
        FALSE,
      ],
    ];
  }

  /**
   * @covers ::addDependency
   * @covers ::validatePackageName
   * @covers ::validatePackageVersion
   * @dataProvider dataProviderAddDependency
   */
  public function testAddDependency(array $initialData, string $package, string $version, mixed $expected, bool $expect_exception = FALSE): void {
    if ($expect_exception) {
      $this->expectException(\Exception::class);
    }

    $m = new ComposerJsonManipulator(json_encode((object) $initialData));
    $m->addDependency($package, $version);
    $this->assertEquals($expected, $m->getFormattedData());
  }

  public static function dataProviderAddDependency(): array {
    return [
      // Adding a dependency to an empty require section.
      [
        [],
        'example/package',
        '^1.0',
        ['require' => ['example/package' => '^1.0']],
      ],

      // Adding a dependency when require section already has packages.
      [
        ['require' => ['existing/package' => '1.0.0']],
        'example/package',
        '^1.0',
        ['require' => ['existing/package' => '1.0.0', 'example/package' => '^1.0']],
      ],

      // Adding a dependency when require section already has packages - order.
      [
        ['require' => ['existing/package' => '1.0.0']],
        'an-example/package',
        '^1.0',
        ['require' => ['an-example/package' => '^1.0', 'existing/package' => '1.0.0']],
      ],

      // Adding a dependency that already exists (should update the version)
      [
        ['require' => ['example/package' => '1.0.0']],
        'example/package',
        '^2.0',
        ['require' => ['example/package' => '^2.0']],
      ],

      // Adding a dependency with invalid package name.
      [
        ['require' => []],
        'invalid/package/name',
        '^1.0',
        NULL,
        TRUE,

      ],

      // Adding a dependency with invalid version.
      [
        ['require' => []],
        'example/package',
        'invalid-version',
        NULL,
        TRUE,
      ],
    ];
  }

  /**
   * @covers ::addDevDependency
   * @covers ::validatePackageName
   * @covers ::validatePackageVersion
   * @dataProvider dataProviderAddDevDependency
   */
  public function testAddDevDependency(array $initialData, string $package, string $version, mixed $expected, bool $expect_exception = FALSE): void {
    if ($expect_exception) {
      $this->expectException(\Exception::class);
    }

    $m = new ComposerJsonManipulator(json_encode((object) $initialData));
    $m->addDevDependency($package, $version);
    $this->assertEquals($expected, $m->getFormattedData());
  }

  public static function dataProviderAddDevDependency(): array {
    return [
      // Adding a dependency to an empty require-dev section.
      [
        [],
        'example/package',
        '^1.0',
        ['require-dev' => ['example/package' => '^1.0']],
      ],

      // Adding a dependency when require-dev section already has packages.
      [
        ['require-dev' => ['existing/package' => '1.0.0']],
        'example/package',
        '^1.0',
        ['require-dev' => ['existing/package' => '1.0.0', 'example/package' => '^1.0']],
      ],

      // Adding a dependency when require-dev already has packages -  order.
      [
        ['require-dev' => ['existing/package' => '1.0.0']],
        'an-example/package',
        '^1.0',
        ['require-dev' => ['an-example/package' => '^1.0', 'existing/package' => '1.0.0']],
      ],

      // Adding a dependency that already exists (should update the version)
      [
        ['require-dev' => ['example/package' => '1.0.0']],
        'example/package',
        '^2.0',
        ['require-dev' => ['example/package' => '^2.0']],
      ],

      // Adding a dependency with invalid package name.
      [
        ['require-dev' => []],
        'invalid/package/name',
        '^1.0',
        NULL,
        TRUE,

      ],

      // Adding a dependency with invalid version.
      [
        ['require-dev' => []],
        'example/package',
        'invalid-version',
        NULL,
        TRUE,
      ],
    ];
  }

  /**
   * @covers ::mergeProperty
   * @covers ::refreshContents
   * @dataProvider dataProviderMergeProperty
   */
  public function testMergeProperty(array $initialData, string $propertyName, array $valueToMerge, bool $sort, mixed $expected): void {
    $m = new ComposerJsonManipulator(json_encode((object) $initialData));

    $m->mergeProperty($propertyName, $valueToMerge, $sort);

    $this->assertEquals($expected, $m->getFormattedData());
  }

  public static function dataProviderMergeProperty(): array {
    return [
      // Merging into an empty property.
      [
        [],
        'extra',
        ['branch-alias' => ['dev-master' => '1.0-dev']],
        FALSE,
        ['extra' => ['branch-alias' => ['dev-master' => '1.0-dev']]],
      ],

      // Merging into an existing property.
      [
        ['extra' => ['branch-alias' => ['dev-master' => '1.0-dev']]],
        'extra',
        ['branch-alias' => ['dev-feature' => '2.0-dev']],
        FALSE,
        ['extra' => ['branch-alias' => ['dev-master' => '1.0-dev', 'dev-feature' => '2.0-dev']]],
      ],

      // Merging and sorting.
      [
        ['extra' => ['b' => 'value1', 'a' => 'value2']],
        'extra',
        ['c' => 'value3'],
        TRUE,
        ['extra' => ['a' => 'value2', 'b' => 'value1', 'c' => 'value3']],
      ],

      // Merging with an empty array.
      [
        ['extra' => ['some-key' => 'some-value']],
        'extra',
        [],
        FALSE,
        ['extra' => ['some-key' => 'some-value']],
      ],

    ];
  }

  /**
   * @covers ::addPropertyAfter
   * @covers ::refreshContents
   * @dataProvider dataProviderAddPropertyAfter
   */
  public function testAddPropertyAfter(array $initialData, string $name, mixed $value, string $after, mixed $expected): void {
    $m = new ComposerJsonManipulator(json_encode((object) $initialData));

    $m->addPropertyAfter($name, $value, $after);

    $this->assertEquals($expected, $m->getFormattedData());
  }

  public static function dataProviderAddPropertyAfter(): array {
    return [
      // Adding a property at the root level.
      [
        ['property1' => 'value1', 'property2' => 'value2'],
        'newProperty',
        'newValue',
        'property1',
        ['property1' => 'value1', 'newProperty' => 'newValue', 'property2' => 'value2'],
      ],

      // Adding a nested property.
      [
        ['parent' => ['child1' => 'value1', 'child2' => 'value2']],
        'parent.newChild',
        'newValue',
        'parent.child1',
        ['parent' => ['child1' => 'value1', 'newChild' => 'newValue', 'child2' => 'value2']],
      ],

      // Adding a property when the 'after' property doesn't exist.
      [
        ['property1' => 'value1'],
        'newProperty',
        'newValue',
        'nonExistingProperty',
        // Adjust based on actual behavior.
        ['property1' => 'value1', 'newProperty' => 'newValue'],
      ],

      // Adding a property when the target parent in 'after' does not exist.
      [
        ['existingProperty' => 'value'],
        'newProperty',
        'newValue',
        'nonExistingParent.afterProperty',
        [
          'existingProperty' => 'value',
        ],
      ],

      // Adding a property to an empty JSON structure.
      [
        [],
        'newProperty',
        'newValue',
        'afterProperty',
        [
          'newProperty' => 'newValue',
        ],
      ],

      // Adding a deeply nested new property.
      [
        ['level1' => ['level2' => ['existingProperty' => 'value']]],
        'level1.level2.newProperty',
        'newValue',
        'level1.level2.existingProperty',
        [
          'level1' => [
            'level2' => [
              'existingProperty' => 'value',
              'newProperty' => 'newValue',
            ],
          ],
        ],
      ],

    ];
  }

}
