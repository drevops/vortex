<?php

declare(strict_types=1);

namespace DrevOps\Customizer\Tests\Unit\Widget;

use DrevOps\Customizer\Config\Field;
use DrevOps\Customizer\Config\FieldType;
use DrevOps\Customizer\Config\Option;
use DrevOps\Customizer\Widget\ConfirmWidget;
use DrevOps\Customizer\Widget\MultiSelectWidget;
use DrevOps\Customizer\Widget\SelectWidget;
use DrevOps\Customizer\Widget\SuggestWidget;
use DrevOps\Customizer\Widget\TextWidget;
use DrevOps\Customizer\Widget\WidgetFactory;
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
   * @param \DrevOps\Customizer\Config\FieldType $type
   *   The field type.
   */
  protected function field(FieldType $type): Field {
    return new Field('f', 'F', '', $type, '');
  }

  /**
   * A choice field of the given type with two options.
   *
   * @param \DrevOps\Customizer\Config\FieldType $type
   *   The field type.
   */
  protected function fieldWithOptions(FieldType $type): Field {
    return new Field('f', 'F', '', $type, '', ['a' => new Option('a', 'A'), 'b' => new Option('b', 'B')]);
  }

}
