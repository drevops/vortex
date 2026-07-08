<?php

declare(strict_types=1);

namespace DrevOps\Tui\Tests\Unit\Widget;

use DrevOps\Tui\Config\Field;
use DrevOps\Tui\Config\FieldType;
use DrevOps\Tui\Config\Option;
use DrevOps\Tui\Widget\ConfirmWidget;
use DrevOps\Tui\Widget\MultiSearchWidget;
use DrevOps\Tui\Widget\MultiSelectWidget;
use DrevOps\Tui\Widget\NumberWidget;
use DrevOps\Tui\Widget\PasswordWidget;
use DrevOps\Tui\Widget\PauseWidget;
use DrevOps\Tui\Widget\SearchWidget;
use DrevOps\Tui\Widget\SelectWidget;
use DrevOps\Tui\Widget\SuggestWidget;
use DrevOps\Tui\Widget\TextareaWidget;
use DrevOps\Tui\Widget\TextWidget;
use DrevOps\Tui\Widget\WidgetFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Tests the widget factory.
 */
#[CoversClass(WidgetFactory::class)]
#[Group('widget')]
final class WidgetFactoryTest extends TestCase {

  public function testCreatesByType(): void {
    $factory = new WidgetFactory();

    $this->assertInstanceOf(TextWidget::class, $factory->create($this->field(FieldType::Text), 'x'));
    $this->assertInstanceOf(ConfirmWidget::class, $factory->create($this->field(FieldType::Confirm), TRUE));
    $this->assertInstanceOf(SelectWidget::class, $factory->create($this->fieldWithOptions(FieldType::Select), 'a'));
    $this->assertInstanceOf(MultiSelectWidget::class, $factory->create($this->fieldWithOptions(FieldType::MultiSelect), ['a']));
    $this->assertInstanceOf(SuggestWidget::class, $factory->create($this->fieldWithOptions(FieldType::Suggest), 'a'));
    $this->assertInstanceOf(NumberWidget::class, $factory->create($this->field(FieldType::Number), 42));
    $this->assertInstanceOf(TextareaWidget::class, $factory->create($this->field(FieldType::Textarea), 'x'));
    $this->assertInstanceOf(PasswordWidget::class, $factory->create($this->field(FieldType::Password), 'x'));
    $this->assertInstanceOf(SearchWidget::class, $factory->create($this->fieldWithOptions(FieldType::Search), 'a'));
    $this->assertInstanceOf(MultiSearchWidget::class, $factory->create($this->fieldWithOptions(FieldType::MultiSearch), ['a']));
    $this->assertInstanceOf(PauseWidget::class, $factory->create($this->field(FieldType::Pause), TRUE));
  }

  public function testNumberSeededFromIntCurrent(): void {
    $widget = (new WidgetFactory())->create($this->field(FieldType::Number), 8080);

    $this->assertSame(8080, $widget->value());
  }

  public function testNumberWithNonNumericCurrentIsEmpty(): void {
    $widget = (new WidgetFactory())->create($this->field(FieldType::Number), 'oops');

    $this->assertSame(0, $widget->value());
  }

  public function testSeedsCurrentValue(): void {
    $widget = (new WidgetFactory())->create($this->field(FieldType::Text), 'Acme');

    $this->assertSame('Acme', $widget->value());
  }

  public function testMultiselectWithNonArrayValueHasNoDefaults(): void {
    $widget = (new WidgetFactory())->create($this->fieldWithOptions(FieldType::MultiSelect), 'notalist');

    $this->assertSame([], $widget->value());
  }

  /**
   * A field of the given type.
   *
   * @param \DrevOps\Tui\Config\FieldType $type
   *   The field type.
   */
  protected function field(FieldType $type): Field {
    return new Field('f', 'F', '', $type, '');
  }

  /**
   * A choice field of the given type with two options.
   *
   * @param \DrevOps\Tui\Config\FieldType $type
   *   The field type.
   */
  protected function fieldWithOptions(FieldType $type): Field {
    return new Field('f', 'F', '', $type, '', ['a' => new Option('a', 'A'), 'b' => new Option('b', 'B')]);
  }

}
