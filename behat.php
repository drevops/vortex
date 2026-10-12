<?php

/**
 * @file
 * Behat configuration.
 */

declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Filter\TagFilter;
use Behat\Config\Formatter\JUnitFormatter;
use Behat\Config\Formatter\ProgressFormatter;
use Behat\Config\GherkinOptions;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\MinkExtension\Context\MinkContext;
use Behat\MinkExtension\ServiceContainer\MinkExtension;
use DrevOps\BehatScreenshotExtension\Context\ScreenshotContext;
use DrevOps\BehatScreenshotExtension\ServiceContainer\BehatScreenshotExtension;
use DrevOps\BehatSteps\Behat\ServiceContainer\BehatStepsExtension;

$suite = (new Suite('default'))
  ->withPaths('%paths.base%/tests/behat/features')
  ->addContext(FeatureContext::class)
  ->addContext(MinkContext::class)
  ->addContext(ScreenshotContext::class);

$default = (new Profile('default', ['autoload' => ['%paths.base%/tests/behat/bootstrap']]))
  // Disable caching during development. It is enabled for profiles below.
  // Allow skipping tests by tagging them with '@skipped'.
  ->withGherkinOptions((new GherkinOptions(['cache' => '']))->withFilter(new TagFilter('~@skipped')))
  ->withSuite($suite)
  // Show test progress and explicit fail information while continuing the test run.
  ->withFormatter(new ProgressFormatter(inlineFailures: TRUE))
  // Output test results in JUnit format.
  ->withFormatter((new JUnitFormatter())->withOutputPath('%paths.base%/.logs/test_results/behat'))
  ->withExtension(new Extension(MinkExtension::class, [
    'base_url' => 'http://nginx:8080',
    'files_path' => '%paths.base%/tests/behat/fixtures',
    'browser_name' => 'chrome',
    'javascript_session' => 'selenium2',
    'sessions' => [
      'browserkit_http' => ['browserkit_http' => NULL],
      'selenium2' => [
        'selenium2' => [
          'wd_host' => 'http://chrome:4444/wd/hub',
          'capabilities' => [
            'browser' => 'chrome',
            'extra_capabilities' => [
              'goog:chromeOptions' => [
                'args' => [
                  // Prevents interference from browser extensions.
                  '--disable-extensions',
                  // Allows tests to open popups without being blocked.
                  '--disable-popup-blocking',
                  // Prevents the translation bar from appearing on non-English pages.
                  '--disable-translate',
                  // Disables CSS animations and transitions for test stability.
                  '--force-prefers-reduced-motion',
                  // Suppresses error dialogs and crash recovery prompts.
                  '--test-type',
                  // Sets the browser window size for consistent test screenshots.
                  '--window-size=1920,1080',
                ],
              ],
            ],
          ],
        ],
      ],
    ],
  ]))
  // Provides integration with Drupal APIs.
  ->withExtension(new Extension(BehatStepsExtension::class, [
    'backends' => ['drupal', 'drush', 'blackbox'],
    'blackbox' => NULL,
    'drupal' => ['drupal_root' => 'web'],
    'drush' => ['root' => 'web'],
    'regions' => [
      'header' => '#header',
      'primary_menu' => '.header-nav',
      'secondary_menu' => '.region.region--secondary-menu',
      'hero' => '.region.region--hero',
      'highlighted' => '.region.region--highlighted',
      'breadcrumb' => '.region.region--breadcrumb',
      'social' => '.social-bar',
      'content_above' => '.region.region--content-above',
      'content' => '.region.region--content',
      'sidebar' => '.region.region--sidebar',
      'content_below' => '.region.region--content-below',
      'footer_top' => '.region.region--footer-top',
      'footer_bottom' => '.region.region--footer-bottom',
    ],
    'selectors' => ['login_form_selector' => 'form#user-login,form#user-login-form', 'logged_in_selector' => 'body.logged-in,body.user-logged-in'],
    'steps' => [
      'message' => [
        'selectors' => [
          'default' => '.messages',
          'error' => '.messages.error,.messages.messages--error',
          'success' => '.messages.status,.messages.messages--status',
          'warning' => '.messages.warning,.messages.messages--warning',
        ],
      ],
    ],
  ]))
  // Capture HTML and PNG screenshots on demand and on failure.
  ->withExtension(new Extension(BehatScreenshotExtension::class, [
    // Directory to save screenshots.
    'dir' => '%paths.base%/.logs/screenshots',
    // Change to FALSE to disable screenshots on failure.
    'on_failed' => TRUE,
    // Always capture full page screenshots.
    'always_fullscreen' => TRUE,
    // Purge screenshots before each run. Use `BEHAT_SCREENSHOT_PURGE=true` to override.
    'purge' => FALSE,
    // Combine per-step screenshots into an animated GIF for each scenario.
    'animation' => [
      // Change to FALSE to disable animated screenshots.
      'enabled' => TRUE,
      // Delay between animated GIF frames, in milliseconds.
      'frame_delay' => 500,
    ],
    // Additional information to include in screenshots.
    'info_types' => ['url', 'feature', 'step', 'datetime'],
  ]));

// Profiles for parallel testing. CI derives the profile number from the index
// of the runner it is on minus 'VORTEX_CI_BEHAT_PROFILE_OFFSET', so profiles
// stay numbered from 'p0' regardless of which runner Behat starts on. Adding a
// runner that runs Behat means adding a profile for its derived number and
// excluding its tag from the 'p0' catch-all below.
// https://www.vortextemplate.com/docs/continuous-integration#test-parallelism

// Runs all tests tagged with '@smoke' or not tagged with '@p1', and not tagged
// with '@skipped'. This is a 'catch-all' profile that runs any tests not tagged
// with '@pX'.
$p0 = (new Profile('p0'))
  ->withGherkinOptions((new GherkinOptions())->withCacheDir('/tmp/behat_gherkin_cache')->withFilter(new TagFilter('@smoke,~@p1&&~@skipped')));

// Runs all tests tagged with '@smoke' or '@p1' and not tagged with '@skipped'.
$p1 = (new Profile('p1'))
  ->withGherkinOptions((new GherkinOptions())->withCacheDir('/tmp/behat_gherkin_cache')->withFilter(new TagFilter('@smoke,@p1&&~@skipped')));

return (new Config())->withProfile($default)->withProfile($p0)->withProfile($p1);
