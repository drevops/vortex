@@ -21,7 +21,6 @@
 
 $finder = new TwigCsFixer\File\Finder();
 $finder->in(__DIR__ . '/web/modules/custom');
-$finder->in(__DIR__ . '/web/themes/custom');
 
 $config = new TwigCsFixer\Config\Config();
 $config->setRuleset($ruleset);
