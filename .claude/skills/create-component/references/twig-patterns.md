# Component Twig patterns

Patterns for templates in `web/themes/custom/your_site_theme/components/`. Each example uses the theme's component IDs; adapt the names to the component at hand.

## Modifier classes from a prop

`button.twig` builds its class list from a `modifier` prop. For a prop with a fixed set of values, add the class only when the prop is set:

```twig
{%
  set classes = [
    'card',
    tone ? 'card--' ~ tone|clean_class,
    featured ? 'card--featured',
  ]
%}
<article{{ attributes.addClass(classes) }}>
```

`addClass()` skips the empty entries, and `|clean_class` turns any value into a safe class name.

## An optional slot with a wrapper

Print the wrapper only when the slot has output:

```twig
{% set actions_output %}{% block actions %}{{ actions }}{% endblock %}{% endset %}
{% if actions_output|trim is not empty %}
  <div class="card__actions">{{ actions_output }}</div>
{% endif %}
```

## A slot with fallback content

The block default prints the slot variable, and the variable falls back to a prop. `button.twig` does the same with its `content` slot and `label` prop:

```twig
{% block body %}{{ body ?: summary }}{% endblock %}
```

## Composing components

Expose a slot instead of rendering a child component inside the template. The caller then decides what fills it:

```twig
{# card.twig #}
{% set actions_output %}{% block actions %}{{ actions }}{% endblock %}{% endset %}

{# The caller #}
{% embed 'your_site_theme:card' with {heading: label, url: url} only %}
  {% block actions %}
    {{ include('your_site_theme:button', {label: 'Read more'|t, url: url}, with_context: false) }}
  {% endblock %}
{% endembed %}
```

Render a child component inside a template only when it is always part of the component, such as an icon in a button. Then list its library in `libraryOverrides.dependencies` when the parent's CSS relies on it.

## Lists of items

Loop over an array prop for plain values, and over a slot for rendered items:

```twig
{% if tags is not empty %}
  <ul class="card__tags">
    {% for tag in tags %}
      <li class="card__tag">{{ tag }}</li>
    {% endfor %}
  </ul>
{% endif %}
```

## Attributes for an inner element

Build attributes for a second element with `create_attribute()`:

```twig
{% set image_attributes = create_attribute() %}
<figure{{ image_attributes.addClass('card__media').setAttribute('aria-hidden', decorative ? 'true' : 'false') }}>
```

When a caller must control that element, declare a prop of type `Drupal\Core\Template\Attribute`, as [schema.md](schema.md) shows, and fall back to a new object: `{% set link_attributes = link_attributes ?: create_attribute() %}`.

## Classes from a render array

A render array of type `component` merges `#attributes` into the component's `attributes`, so a caller adds classes without a new prop:

```php
$build['card'] = [
  '#type' => 'component',
  '#component' => 'your_site_theme:card',
  '#props' => ['heading' => $title],
  '#attributes' => ['class' => ['card--compact']],
];
```

A scalar slot value in `#slots` renders as plain text. Pass markup as a render array, such as `['#markup' => $safe_markup]`, and only for markup that is already sanitized.

## Escaping

- A string prop: `{{ heading }}` prints it escaped.
- A slot, render array or markup object: `{{ body }}` prints the rendered markup.
- A translatable string: `{{ 'Read more'|t }}`.
- A string with placeholders: `{{ 'By @name'|t({'@name': author}) }}` escapes the placeholder values.
- Trusted, already sanitized markup: `{{ processed_text|raw }}`. Never use `|raw` on a prop or on user input.
