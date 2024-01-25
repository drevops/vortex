<?php

namespace DrevOps\DevTool\Tests\Unit\Utils;

use DrevOps\DevTool\Utils\Arrays;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \DrevOps\DevTool\Utils\Arrays
 */
class ArraysTest extends TestCase {

  /**
   * Form array to check.
   *
   * @var array
   */
  protected $form;

  /**
   * Array of parents for the nested element.
   *
   * @var array
   */
  protected $parents;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a form structure with a nested element.
    $this->form['details']['element'] = [
      '#value' => 'Nested element',
    ];

    // Set up parent array.
    $this->parents = ['details', 'element'];
  }

  /**
   * @covers ::insertAfterKey
   * @dataProvider providerForInsertAfterKey
   */
  public function testInsertAfterKey(array $originalArray, string $key, string $newKey, mixed $newValue, mixed $expectedArray): void {
    $result = Arrays::insertAfterKey($originalArray, $key, $newKey, $newValue);
    $this->assertEquals($expectedArray, $result);
  }

  public static function providerForInsertAfterKey(): array {
    return [
      // Test insertion in the middle of array.
      [
        ['a' => 1, 'b' => 2, 'c' => 3],
        'a',
        'newKey',
        'newValue',
        ['a' => 1, 'newKey' => 'newValue', 'b' => 2, 'c' => 3],
      ],
      // Test insertion at the end.
      [
        ['a' => 1, 'b' => 2],
        'b',
        'newKey',
        'newValue',
        ['a' => 1, 'b' => 2, 'newKey' => 'newValue'],
      ],
      // Test insertion when key does not exist.
      [
        ['a' => 1, 'b' => 2],
        'nonExistingKey',
        'newKey',
        'newValue',
        ['a' => 1, 'b' => 2, 'newKey' => 'newValue'],
      ],
      // Test insertion in an empty array.
      [
        [],
        'anyKey',
        'newKey',
        'newValue',
        ['newKey' => 'newValue'],
      ],

      // Test with object as new value.
      [
        ['a' => 1, 'b' => 2],
        'a',
        'newKey',
        (object) ['property' => 'value'],
        ['a' => 1, 'newKey' => (object) ['property' => 'value'], 'b' => 2],
      ],
      // Test with array as new value.
      [
        ['a' => 1, 'b' => 2],
        'b',
        'newKey',
        ['nestedKey' => 'nestedValue'],
        ['a' => 1, 'b' => 2, 'newKey' => ['nestedKey' => 'nestedValue']],
      ],
    ];
  }

  /**
   * Tests getting nested array values.
   *
   * @covers ::getValue
   */
  public function testGetValue(): void {
    // Verify getting a value of a nested element.
    $value = Arrays::getValue($this->form, $this->parents);
    $this->assertSame('Nested element', $value['#value'], 'Nested element value found.');

    // Verify changing a value of a nested element by reference.
    $value = &Arrays::getValue($this->form, $this->parents);
    $value['#value'] = 'New value';
    $value = Arrays::getValue($this->form, $this->parents);
    $this->assertSame('New value', $value['#value'], 'Nested element value was changed by reference.');
    $this->assertSame('New value', $this->form['details']['element']['#value'], 'Nested element value was changed by reference.');

    // Verify that an existing key is reported back.
    $key_exists = NULL;
    Arrays::getValue($this->form, $this->parents, $key_exists);
    $this->assertTrue($key_exists, 'Existing key found.');

    // Verify that a non-existing key is reported back and throws no errors.
    $key_exists = NULL;
    $parents = $this->parents;
    $parents[] = 'foo';
    Arrays::getValue($this->form, $parents, $key_exists);
    $this->assertFalse($key_exists, 'Non-existing key not found.');
  }

  /**
   * Tests setting nested array values.
   *
   * @covers ::setValue
   */
  public function testSetValue(): void {
    $new_value = [
      '#value' => 'New value',
      '#required' => TRUE,
    ];

    // Verify setting the value of a nested element.
    Arrays::setValue($this->form, $this->parents, $new_value);
    $this->assertSame('New value', $this->form['details']['element']['#value'], 'Changed nested element value found.');
    $this->assertTrue($this->form['details']['element']['#required'], 'New nested element value found.');
  }

  /**
   * Tests force-setting values.
   *
   * @covers ::setValue
   */
  public function testSetValueForce(): void {
    $new_value = [
      'one',
    ];
    $this->form['details']['non-array-parent'] = 'string';
    $parents = ['details', 'non-array-parent', 'child'];
    Arrays::setValue($this->form, $parents, $new_value, TRUE);
    $this->assertSame($new_value, $this->form['details']['non-array-parent']['child'], 'The nested element was not forced to the new value.');
  }

  /**
   * Tests Arrays::mergeDeepArray().
   *
   * @covers ::mergeDeep
   * @covers ::mergeDeepArray
   */
  public function testMergeDeepArray(): void {
    $link_options_1 = [
      'fragment' => 'x',
      'attributes' => ['title' => 'X', 'class' => ['a', 'b']],
      'language' => 'en',
    ];
    $link_options_2 = [
      'fragment' => 'y',
      'attributes' => ['title' => 'Y', 'class' => ['c', 'd']],
      'absolute' => TRUE,
    ];
    $expected = [
      'fragment' => 'y',
      'attributes' => ['title' => 'Y', 'class' => ['a', 'b', 'c', 'd']],
      'language' => 'en',
      'absolute' => TRUE,
    ];
    $this->assertSame($expected, Arrays::mergeDeepArray([$link_options_1, $link_options_2]), 'Arrays::mergeDeepArray() returned a properly merged array.');
    // Test wrapper function, Arrays::mergeDeep().
    $this->assertSame($expected, Arrays::mergeDeep($link_options_1, $link_options_2), 'Arrays::mergeDeep() returned a properly merged array.');
  }

  /**
   * Tests that arrays with implicit keys are appended, not merged.
   *
   * @covers ::mergeDeepArray
   */
  public function testMergeImplicitKeys(): void {
    $a = [
      'subkey' => ['X', 'Y'],
    ];
    $b = [
      'subkey' => ['X'],
    ];

    // Drupal core behavior.
    $expected = [
      'subkey' => ['X', 'Y', 'X'],
    ];
    $actual = Arrays::mergeDeepArray([$a, $b]);
    $this->assertSame($expected, $actual, 'drupal_array_merge_deep() creates new numeric keys in the implicit sequence.');
  }

  /**
   * Tests that even with explicit keys, values are appended, not merged.
   *
   * @covers ::mergeDeepArray
   */
  public function testMergeExplicitKeys(): void {
    $a = [
      'subkey' => [
        0 => 'A',
        1 => 'B',
      ],
    ];
    $b = [
      'subkey' => [
        0 => 'C',
        1 => 'D',
      ],
    ];

    // Drupal core behavior.
    $expected = [
      'subkey' => [
        0 => 'A',
        1 => 'B',
        2 => 'C',
        3 => 'D',
      ],
    ];
    $actual = Arrays::mergeDeepArray([$a, $b]);
    $this->assertSame($expected, $actual, 'drupal_array_merge_deep() creates new numeric keys in the explicit sequence.');
  }

  /**
   * Tests that array keys values on the first array are ignored when merging.
   *
   * Even if the initial ordering would place the data from the second array
   * before those in the first one, they are still appended, and the keys on
   * the first array are deleted and regenerated.
   *
   * @covers ::mergeDeepArray
   */
  public function testMergeOutOfSequenceKeys(): void {
    $a = [
      'subkey' => [
        10 => 'A',
        30 => 'B',
      ],
    ];
    $b = [
      'subkey' => [
        20 => 'C',
        0 => 'D',
      ],
    ];

    // Drupal core behavior.
    $expected = [
      'subkey' => [
        0 => 'A',
        1 => 'B',
        2 => 'C',
        3 => 'D',
      ],
    ];
    $actual = Arrays::mergeDeepArray([$a, $b]);
    $this->assertSame($expected, $actual, 'drupal_array_merge_deep() ignores numeric key order when merging.');
  }

}
