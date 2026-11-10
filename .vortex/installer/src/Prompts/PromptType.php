<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

use AlexSkrypnyk\Prompty\Prompty;

/**
 * Prompt input types covering all Prompty widgets.
 */
enum PromptType: string {

  case Text = 'text';

  case Select = 'select';

  case MultiSelect = 'multiselect';

  case Confirm = 'confirm';

  case Suggest = 'suggest';

  /**
   * Get the Prompty widget for this type.
   */
  public function promptFunction(): \Closure {
    return match ($this) {
      self::Text => Prompty::text(...),
      self::Select => Prompty::select(...),
      self::MultiSelect => Prompty::multiselect(...),
      self::Confirm => Prompty::confirm(...),
      self::Suggest => Prompty::suggest(...),
    };
  }

}
