<?php

declare(strict_types=1);

namespace Drupal\Tests\light_saber\FunctionalJavascript;

use PHPUnit\Framework\Attributes\Group;

/**
 * Class ExampleTest.
 *
 * Example functional JavaScript test case class.
 *
 * @package Drupal\light_saber\Tests
 */
#[Group('LightSaber')]
class ExampleTest extends LightSaberFunctionalJavascriptTestBase {

  /**
   * Test that a page can be loaded and JavaScript is functional.
   */
  public function testPageLoad(): void {
    $this->drupalGet('<front>');

    // Verify that the page loaded by checking for a page element.
    $result = $this->assertSession()->waitForElement('css', 'html');
    $this->assertNotNull($result, 'Page HTML element is present.');

    $this->takeScreenshot('theme_page_load');
  }

  /**
   * Test that JavaScript can be executed in the browser.
   */
  public function testJavascriptExecution(): void {
    $this->drupalGet('<front>');

    // Execute JavaScript and verify the result.
    $result = $this->getSession()->evaluateScript('1 + 1');
    $this->assertEquals(2, $result);

    $this->takeScreenshot('theme_js_execution');
  }

}
