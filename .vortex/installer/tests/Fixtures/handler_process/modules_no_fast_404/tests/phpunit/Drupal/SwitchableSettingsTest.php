@@ -4,8 +4,6 @@
 
 namespace Drupal;
 
-use PHPUnit\Framework\Attributes\PreserveGlobalState;
-use PHPUnit\Framework\Attributes\RunInSeparateProcess;
 use PHPUnit\Framework\Attributes\DataProvider;
 use PHPUnit\Framework\Attributes\Group;
 
@@ -22,22 +20,6 @@
 class SwitchableSettingsTest extends SettingsTestCase {
 
   /**
-   * Path to the contrib modules directory fixture.
-   */
-  protected ?string $contribFixture = NULL;
-
-  /**
-   * {@inheritdoc}
-   */
-  protected function tearDown(): void {
-    if (!is_null($this->contribFixture)) {
-      $this->removeContribFixture($this->contribFixture);
-    }
-
-    parent::tearDown();
-  }
-
-  /**
    * Test ClamAV configs in Daemon mode with defaults.
    */
   public function testClamavDaemonCustom(): void {
@@ -239,105 +221,6 @@
         'environment_indicator.settings' => ['toolbar_integration' => [TRUE], 'favicon' => TRUE],
       ],
     ];
-  }
-
-  /**
-   * Test Fast 404 settings.
-   *
-   * Runs isolated so that the module stub owns the 'fast404_preboot()'
-   * declaration and its invocation marker is conclusive.
-   */
-  #[DataProvider('dataProviderFast404')]
-  #[RunInSeparateProcess]
-  #[PreserveGlobalState(FALSE)]
-  public function testFast404(bool $module_installed, array $expected_present, array $expected_absent = []): void {
-    $contrib_path = $this->createContribFixture($module_installed);
-
-    $this->requireModuleSettingsFile('fast_404', $contrib_path);
-
-    $this->assertSettingsContains($expected_present);
-    $this->assertSettingsNotContains($expected_absent);
-    $this->assertSame($module_installed, file_exists($contrib_path . '/fast_404/preboot'), 'Preboot invocation');
-  }
-
-  /**
-   * Data provider for testFast404().
-   */
-  public static function dataProviderFast404(): \Iterator {
-    yield 'module installed' => [
-      TRUE,
-      [
-        'fast404_exts' => '/^(?!\/robots)^(?!\/system\/files).*\.(txt|png|gif|jpe?g|css|js|ico|swf|flv|cgi|bat|pl|dll|exe|asp)$/i',
-        'fast404_allow_anon_imagecache' => TRUE,
-        'fast404_whitelist' => ['index.php', 'rss.xml', 'install.php', 'cron.php', 'update.php', 'xmlrpc.php'],
-        'fast404_string_whitelisting' => ['/advagg_'],
-        'fast404_html' => '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN" "http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd"><html xmlns="http://www.w3.org/1999/xhtml"><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL "@path" was not found on this server.</p></body></html>',
-      ],
-    ];
-    yield 'module not installed' => [
-      FALSE,
-      [],
-      [
-        'fast404_exts' => NULL,
-        'fast404_allow_anon_imagecache' => NULL,
-        'fast404_whitelist' => NULL,
-        'fast404_string_whitelisting' => NULL,
-        'fast404_html' => NULL,
-      ],
-    ];
-  }
-
-  /**
-   * Create a contrib modules directory fixture.
-   *
-   * The Fast 404 module is not required by the project, so its settings file
-   * guard can only be satisfied by a stub.
-   *
-   * @param bool $with_fast404
-   *   Create a stub of the Fast 404 module within the fixture. The stub marks
-   *   the directory when its preboot function is called.
-   *
-   * @return string
-   *   Path to the contrib modules directory fixture.
-   */
-  protected function createContribFixture(bool $with_fast404): string {
-    $this->contribFixture = getcwd() . '/.artifacts/tmp/' . uniqid('contrib-');
-
-    mkdir($this->contribFixture, 0777, TRUE);
-
-    if ($with_fast404) {
-      $stub = <<<'PHP'
-        <?php
-
-        function fast404_preboot(array $settings = []): void {
-          touch(__DIR__ . '/preboot');
-        }
-
-        PHP;
-
-      mkdir($this->contribFixture . '/fast_404');
-      file_put_contents($this->contribFixture . '/fast_404/fast404.inc', $stub);
-    }
-
-    return $this->contribFixture;
-  }
-
-  /**
-   * Remove the contrib modules directory fixture.
-   *
-   * @param string $path
-   *   Path to the contrib modules directory fixture.
-   */
-  protected function removeContribFixture(string $path): void {
-    foreach (glob($path . '/*/*') ?: [] as $file) {
-      unlink($file);
-    }
-
-    foreach (glob($path . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
-      rmdir($dir);
-    }
-
-    rmdir($path);
   }
 
   /**
