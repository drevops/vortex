<?php

declare(strict_types=1);

namespace DrevOps\Tui\Config;

/**
 * The set of supported field (widget) types.
 *
 * @package DrevOps\Tui\Config
 */
enum FieldType: string {

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
