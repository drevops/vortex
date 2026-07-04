<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Config;

/**
 * The set of supported field (widget) types.
 *
 * @package DrevOps\Customizer\Config
 */
enum FieldType: string {

  case Text = 'text';
  case Select = 'select';
  case MultiSelect = 'multiselect';
  case Confirm = 'confirm';
  case Suggest = 'suggest';

}
