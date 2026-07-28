<?php

declare(strict_types=1);

namespace Drupal\Tests\sw_base\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example functional JavaScript test case class.
 *
 * @package Drupal\sw_base\Tests
 */
#[Group('SwBase')]
class ExampleTest extends SwBaseFunctionalJavascriptTestBase {

  /**
   * Test that a page can be loaded and JavaScript is functional.
   */
  public function testPageLoad(): void {
    $this->drupalGet('<front>');

    // Verify that the page loaded by checking for a page element.
    $result = $this->assertSession()->waitForElement('css', 'html');
    $this->assertNotNull($result, 'Page HTML element is present.');

    $this->takeScreenshot('module_page_load');
  }

  /**
   * Test that JavaScript can be executed in the browser.
   */
  public function testJavascriptExecution(): void {
    $this->drupalGet('<front>');

    // Execute JavaScript and verify the result.
    $result = $this->getSession()->evaluateScript('1 + 1');
    $this->assertEquals(2, $result);

    $this->takeScreenshot('module_js_execution');
  }

}
