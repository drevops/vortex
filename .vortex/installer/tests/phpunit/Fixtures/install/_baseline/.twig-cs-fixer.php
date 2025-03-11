<?php

declare(strict_types=1);

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addRule(new TwigCsFixer\Rules\Delimiter\BlockNameSpacingRule());
$ruleset->addRule(new TwigCsFixer\Rules\Delimiter\DelimiterSpacingRule());
$ruleset->addRule(new TwigCsFixer\Rules\Function\NamedArgumentSpacingRule());
$ruleset->addRule(new TwigCsFixer\Rules\Operator\OperatorNameSpacingRule());
$ruleset->addRule(new TwigCsFixer\Rules\Operator\OperatorSpacingRule());
$ruleset->addRule(new TwigCsFixer\Rules\Punctuation\PunctuationSpacingRule());
$ruleset->addRule(new TwigCsFixer\Rules\Punctuation\TrailingCommaMultiLineRule());
$ruleset->addRule(new TwigCsFixer\Rules\Punctuation\TrailingCommaSingleLineRule());
$ruleset->addRule(new TwigCsFixer\Rules\String\HashQuoteRule());
$ruleset->addRule(new TwigCsFixer\Rules\String\SingleQuoteRule());
$ruleset->addRule(new TwigCsFixer\Rules\Variable\VariableNameRule());
$ruleset->addRule(new TwigCsFixer\Rules\Whitespace\BlankEOFRule());
$ruleset->addRule(new TwigCsFixer\Rules\Whitespace\EmptyLinesRule());
$ruleset->addRule(new TwigCsFixer\Rules\Whitespace\IndentRule(2));
$ruleset->addRule(new TwigCsFixer\Rules\Whitespace\TrailingSpaceRule());

$finder = new TwigCsFixer\File\Finder();
$finder->in(__DIR__ . '/web/modules/custom');
$finder->in(__DIR__ . '/web/themes/custom');

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->setFinder($finder);
$config->allowNonFixableRules();
$config->addTokenParser(new Drupal\Core\Template\TwigTransTokenParser());

return $config;
