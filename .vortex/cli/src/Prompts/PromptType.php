<?php

declare(strict_types=1);

namespace DrevOps\VortexCli\Prompts;

/**
 * The kinds of question a handler can declare.
 *
 * @package DrevOps\VortexCli\Prompts
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

}
