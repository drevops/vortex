@@ -5,6 +5,8 @@
 namespace Drupal\sw_base\Plugin\DeployStep;
 
 use DrevOps\EnvironmentDetector\Environment;
+use Drupal\Core\DrupalKernelInterface;
+use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
 use Drupal\Core\Extension\ModuleHandlerInterface;
 use Drupal\Core\Extension\ModuleInstallerInterface;
 use Drupal\Core\StringTranslation\TranslatableMarkup;
@@ -45,6 +47,16 @@
   protected ModuleHandlerInterface $moduleHandler;
 
   /**
+   * The key value store factory.
+   */
+  protected KeyValueFactoryInterface $keyValueFactory;
+
+  /**
+   * The Drupal kernel.
+   */
+  protected DrupalKernelInterface $kernel;
+
+  /**
    * {@inheritdoc}
    */
   public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
@@ -51,6 +63,8 @@
     $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
     $instance->moduleInstaller = $container->get('module_installer');
     $instance->moduleHandler = $container->get('module_handler');
+    $instance->keyValueFactory = $container->get('keyvalue');
+    $instance->kernel = $container->get('kernel');
 
     return $instance;
   }
@@ -75,9 +89,26 @@
     // aborts.
     $this->moduleInstaller->install([
       'navigation',
+      'storybook',
     ]);
     if ($this->moduleHandler->moduleExists('toolbar')) {
       $this->moduleInstaller->uninstall(['toolbar']);
+    }
+
+    // Storybook renders every story through Drupal, so recompiling templates on
+    // each request shows component edits without a cache rebuild. Only the
+    // development settings change: 'drush theme:dev' also writes aggregation
+    // settings that would appear in exported configuration.
+    if ($this->environment() === Environment::LOCAL) {
+      $this->keyValueFactory->get('development_settings')->setMultiple([
+        'twig_debug' => TRUE,
+        'twig_cache_disable' => TRUE,
+        'disable_rendered_output_cache_bins' => TRUE,
+      ]);
+
+      // The development settings are applied when the container is compiled,
+      // so the next request compiles a new container.
+      $this->kernel->invalidateContainer();
     }
 
     $this->moduleInstaller->install([
