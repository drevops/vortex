<?php

declare(strict_types=1);

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer());
$ruleset->overrideRule(new TwigCsFixer\Rules\Whitespace\IndentRule(2));

$finder = new TwigCsFixer\File\Finder();
$finder->in(__DIR__ . '/web/modules/custom');
$finder->in(__DIR__ . '/web/themes/custom');

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->setFinder($finder);
$config->allowNonFixableRules();
$config->addTokenParser(new Drupal\Core\Template\TwigTransTokenParser());

return $config;
