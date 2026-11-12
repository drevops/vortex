<?php

declare(strict_types=1);

namespace DrevOps\VortexInstaller\Prompts;

/**
 * Prompt input types, valued as the Prompty widget names.
 */
enum PromptType: string {

  case Text = 'text';

  case Select = 'select';

  case MultiSelect = 'multiselect';

  case Confirm = 'confirm';

  case Suggest = 'suggest';

}
