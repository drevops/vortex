<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multisearch;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\number;
use function Laravel\Prompts\password;
use function Laravel\Prompts\pause;
use function Laravel\Prompts\search;
use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;
use function Laravel\Prompts\text;
use function Laravel\Prompts\textarea;

/**
 * Prompt input types covering all Laravel Prompts input types.
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
   * Get the Laravel Prompts function for this type.
   */
  public function promptFunction(): \Closure {
    return match ($this) {
      self::Text => text(...),
      self::Select => select(...),
      self::MultiSelect => multiselect(...),
      self::Confirm => confirm(...),
      self::Suggest => suggest(...),
      self::Number => number(...),
      self::Textarea => textarea(...),
      self::Password => password(...),
      self::Search => search(...),
      self::MultiSearch => multisearch(...),
      self::Pause => pause(...),
    };
  }

}
