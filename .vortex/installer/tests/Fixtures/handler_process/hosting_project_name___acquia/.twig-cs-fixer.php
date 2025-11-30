@@ -7,8 +7,8 @@
 $ruleset->overrideRule(new TwigCsFixer\Rules\Whitespace\IndentRule(2));
 
 $finder = new TwigCsFixer\File\Finder();
-$finder->in(__DIR__ . '/web/modules/custom');
-$finder->in(__DIR__ . '/web/themes/custom');
+$finder->in(__DIR__ . '/docroot/modules/custom');
+$finder->in(__DIR__ . '/docroot/themes/custom');
 
 $config = new TwigCsFixer\Config\Config();
 $config->setRuleset($ruleset);
