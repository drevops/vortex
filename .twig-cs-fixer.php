<?php

declare(strict_types = 1);

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\Twig());

$finder = new TwigCsFixer\File\Finder();
$finder->in(__DIR__ . '/[your-webroot]/modules/custom');
$finder->in(__DIR__ . '/[your-webroot]/themes/custom');

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->setFinder($finder);

return $config;
