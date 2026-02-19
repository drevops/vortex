<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

/**
 * Prompt input types covering all Laravel Prompts input types.
 *
 * @package DrevOps\VortexInstaller\Prompts
 */
enum PromptType: string {

  case Text = 'text';

  case Select = 'select';

  case MultiSelect = 'multiselect';

  case Confirm = 'confirm';

  case Suggest = 'suggest';

  case Number = 'number';

  case Textarea = 'textarea';

  case Password = 'password';

  case Search = 'search';

  case MultiSearch = 'multisearch';

  case Pause = 'pause';

  /**
   * Get the Laravel Prompts function name for this type.
   */
  public function promptFunction(): string {
    return $this->value;
  }

}
