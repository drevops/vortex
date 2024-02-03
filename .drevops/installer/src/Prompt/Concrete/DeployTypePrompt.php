<?php

namespace DrevOps\Installer\Prompt\Concrete;

use DrevOps\Installer\Bag\Answers;
use DrevOps\Installer\Bag\Config;
use DrevOps\Installer\Prompt\AbstractChoicePrompt;
use DrevOps\Installer\Utils\DotEnv;
use DrevOps\Installer\Utils\Env;
use DrevOps\Installer\Utils\Formatter;
use DrevOps\Installer\Utils\Strings;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Deploy type prompt.
 */
class DeployTypePrompt extends AbstractChoicePrompt {

  /**
   * The prompt ID.
   */
  final const ID = 'deploy_type';

  final const CHOICE_ARTIFACT = 'artifact';

  final const CHOICE_WEBHOOK = 'webhook';

  final const CHOICE_DOCKER = 'docker';

  final const CHOICE_LAGOON = 'lagoon';

  final const CHOICE_NONE = 'none';

  public function __construct(SymfonyStyle $io) {
    parent::__construct($io);
    $this->isMultiselect = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function title(): string {
    return 'Deployment';
  }

  /**
   * {@inheritdoc}
   */
  public static function question(): string {
    return 'How do you deploy your code to the hosting?';
  }

  /**
   * {@inheritdoc}
   */
  public static function choices(): array {
    return [
      self::CHOICE_ARTIFACT,
      self::CHOICE_WEBHOOK,
      self::CHOICE_DOCKER,
      self::CHOICE_LAGOON,
      self::CHOICE_NONE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function discoveredValue(Config $config, Answers $answers): mixed {
    return DotEnv::getValueFromDstDotenv($config->getDstDir(), Env::DEPLOY_TYPES);
  }

  /**
   * {@inheritdoc}
   */
  protected function validator(mixed $value, Config $config, Answers $answers): void {
    parent::validator($value, $config, $answers);

    if (count($value) > 1 && in_array(self::CHOICE_NONE, $value)) {
      throw new \InvalidArgumentException(sprintf('You can not choose "%s" and other deploy types at the same time.', self::CHOICE_NONE));
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getFormattedValue(mixed $value): string {
    $value = is_array($value) ? Strings::listToString($value) : $value;
    $value = $value == self::CHOICE_NONE ? NULL : $value;

    return Formatter::formatNotEmpty($value, 'Disabled');
  }

}
