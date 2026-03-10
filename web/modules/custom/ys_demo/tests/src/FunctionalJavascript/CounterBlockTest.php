<?php

declare(strict_types=1);

namespace Drupal\Tests\ys_demo\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for the CounterBlock JavaScript interactions.
 *
 * @package Drupal\ys_demo\Tests
 */
#[Group('YsDemo')]
class CounterBlockTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'block', 'ys_demo'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->drupalPlaceBlock('ys_demo_counter_block', [
      'region' => 'content',
      'id' => 'ys_demo_counter_block',
    ]);
  }

  /**
   * Tests that the counter block renders on the page.
   */
  public function testCounterBlockRenders(): void {
    $this->drupalGet('<front>');

    $block = $this->assertSession()->waitForElement('css', '[data-ys-demo-counter]');
    $this->assertNotNull($block, 'Counter block is present on the page.');

    $value = $this->assertSession()->elementExists('css', '[data-counter-value]');
    $this->assertEquals('0', $value->getText());
  }

  /**
   * Tests the increment button increases the counter value.
   */
  public function testIncrement(): void {
    $this->drupalGet('<front>');

    $this->assertSession()->waitForElement('css', '[data-ys-demo-counter]');

    $this->click('[data-counter-action="increment"]');
    $this->assertCounterValue('1');

    $this->click('[data-counter-action="increment"]');
    $this->assertCounterValue('2');
  }

  /**
   * Tests the decrement button decreases the counter value.
   */
  public function testDecrement(): void {
    $this->drupalGet('<front>');

    $this->assertSession()->waitForElement('css', '[data-ys-demo-counter]');

    $this->click('[data-counter-action="decrement"]');
    $this->assertCounterValue('-1');
  }

  /**
   * Tests the reset button resets the counter to zero.
   */
  public function testReset(): void {
    $this->drupalGet('<front>');

    $this->assertSession()->waitForElement('css', '[data-ys-demo-counter]');

    // Increment a few times.
    $this->click('[data-counter-action="increment"]');
    $this->click('[data-counter-action="increment"]');
    $this->click('[data-counter-action="increment"]');
    $this->assertCounterValue('3');

    // Reset.
    $this->click('[data-counter-action="reset"]');
    $this->assertCounterValue('0');
  }

  /**
   * Asserts the counter display shows the expected value.
   *
   * @param string $expected
   *   The expected counter value text.
   */
  protected function assertCounterValue(string $expected): void {
    $result = $this->assertSession()->waitForElementVisible('css', '[data-counter-value]');
    $this->assertNotNull($result);
    $this->assertEquals($expected, $result->getText());
  }

}
