@@ -20,8 +20,8 @@
 $ruleset->addRule(new TwigCsFixer\Rules\Whitespace\TrailingSpaceRule());
 
 $finder = new TwigCsFixer\File\Finder();
-$finder->in(__DIR__ . '/web/modules/custom');
-$finder->in(__DIR__ . '/web/themes/custom');
+$finder->in(__DIR__ . '/docroot/modules/custom');
+$finder->in(__DIR__ . '/docroot/themes/custom');
 
 $config = new TwigCsFixer\Config\Config();
 $config->setRuleset($ruleset);
