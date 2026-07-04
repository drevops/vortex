<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Input;

/**
 * Named special keys recognised by the widgets.
 *
 * @package DrevOps\Customizer\Input
 */
enum KeyName {

  case Up;
  case Down;
  case Left;
  case Right;
  case Enter;
  case Escape;
  case Space;
  case Backspace;
  case Delete;
  case Tab;
  case Home;
  case End;
  case PageUp;
  case PageDown;
  case MouseWheelUp;
  case MouseWheelDown;

}
