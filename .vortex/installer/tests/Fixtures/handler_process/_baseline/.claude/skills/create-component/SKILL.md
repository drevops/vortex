---
name: create-component
description: Use when asked to create, add, change, restyle or debug a Single Directory Component (SDC) in the site's custom theme, write or fix a `*.component.yml` schema or a component Twig template, add CSS or JavaScript to a component, or render a component from another template or a PHP render array.
---

# Create a component

Build Single Directory Components in the custom theme `star_wars` the way the theme's own sample component is built. The theme lives in `web/themes/custom/star_wars/` and its components live in `web/themes/custom/star_wars/components/<name>/`.

Run every command from the project root. Every command goes through `ahoy`, 1 command per Bash call. Never run `npm`, `drush` or `composer` on the host.

## 1. Read before writing

1. Read the theme documentation in `.vortex/docs/content/development/themes.mdx`, published at https://www.vortextemplate.com/docs/development/themes. It covers the build commands, the file layout and the sample component. Don't restate it to the developer; link to it.
2. Read every file in `web/themes/custom/star_wars/components/button/`. It is the model for naming, schema style, template comments and CSS. Match it.
3. Read `web/themes/custom/star_wars/templates/layout/region--footer-bottom.html.twig`, which renders the button from a regular template.
4. For a change to an existing component, search the codebase for its ID, such as `star_wars:button`, in `web/themes/custom/` and `web/modules/custom/`. List every caller before changing a prop or slot.

## 2. Decide props and slots

Settle the interface before writing files. A component is a contract, and every caller depends on its prop and slot names.

- A **prop** is typed data that the template makes a decision with: a label, a URL, a heading level, a style modifier, a flag. Give each prop a JSON Schema `type`, and an `enum` when the values are a fixed set.
- A **slot** is renderable content that the template prints without inspecting it: a render array, formatted field output, another component, a block of markup.
- Anything that can hold HTML is a slot. Never pass markup in a string prop; Twig escapes it, and `|raw` on a prop opens an injection hole.
- Name props and slots in `snake_case`. Don't declare `attributes`; Drupal passes it to every component.
- When a component needs a value computed in PHP, such as a formatted date, compute it in the caller. Preprocess hooks don't run for components.

## 3. Create the directory and the schema

The directory name, the `*.component.yml` base name and the `*.twig` base name are identical. The component ID is `star_wars:<name>`.

```text
components/card/
├── card.component.yml   # Schema: props and slots
├── card.twig            # Template (".twig", not ".html.twig")
├── card.css             # Optional, attached when the component renders
└── card.js              # Optional, attached when the component renders
```

Write the schema in block YAML, in the same order and style as `button.component.yml`: `$schema`, `name`, `status`, `description`, `props`, `slots`. Give every prop and slot a `title` and a `description`.

```yaml
$schema: https://git.drupalcode.org/project/drupal/-/raw/HEAD/core/assets/schemas/v1/metadata.schema.json
name: Card
status: experimental
description: A teaser with a linked heading, optional media and a body.
props:
  type: object
  required:
    - heading
  properties:
    heading:
      type: string
      title: Heading
      description: Visible heading text.
    url:
      type: string
      title: URL
      description: Optional destination. When set, the heading becomes a link.
    heading_level:
      type: integer
      title: Heading level
      description: Level of the heading element. Defaults to 2.
      enum:
        - 2
        - 3
        - 4
slots:
  media:
    title: Media
    description: Optional image or video rendered above the heading.
  body:
    title: Body
    description: Teaser content rendered below the heading.
```

See [references/schema.md](references/schema.md) for prop types, `libraryOverrides`, variants and replacing a component.

## 4. Write the template

Start the template with the `@file` docblock that `button.twig` uses, then write the markup.

```twig
{#
/**
 * @file
 * Card component.
 *
 * Renders a heading, linked when "url" is set, with optional "media" and
 * "body" slots.
 */
#}
{% set level = heading_level|default(2) %}
{% set media_output %}{% block media %}{{ media }}{% endblock %}{% endset %}
{% set body_output %}{% block body %}{{ body }}{% endblock %}{% endset %}
<article{{ attributes.addClass('card') }}>
  {% if media_output|trim is not empty %}
    <div class="card__media">{{ media_output }}</div>
  {% endif %}
  <h{{ level }} class="card__heading">
    {% if url %}
      {% set link_attributes = create_attribute({'href': url}) %}
      <a{{ link_attributes.addClass('card__link') }}>{{ heading }}</a>
    {% else %}
      {{ heading }}
    {% endif %}
  </h{{ level }}>
  {% if body_output|trim is not empty %}
    <div class="card__body">{{ body_output }}</div>
  {% endif %}
</article>
```

Follow these rules in every component template:

- Print `attributes` on the root element and add classes with `attributes.addClass()`. Callers and modules pass classes, IDs and ARIA attributes through it. Never print the root `class` attribute by hand.
- For a second element that needs an attribute set, build it with `create_attribute()` and chain `addClass()`, `setAttribute()` or `removeClass()`. Don't concatenate attribute strings.
- Output each slot through a block whose default prints the slot variable, as `{% block body %}{{ body }}{% endblock %}`. That form works for `include()`, `embed` and render arrays alike. Capture it with `{% set %}` when the template must test whether the slot is empty.
- Branch on props only. Test a slot for emptiness, never for its contents.
- Twig escapes every `{{ }}` output. Render arrays and markup objects arrive already safe, so they print without a filter. Use `|raw` only for markup that Drupal already sanitized, such as a processed text field, and never on a prop or user input.
- Translate fixed strings with `|t`, as `{{ 'Read more'|t }}`.
- Use semantic elements and expose a `heading_level` prop instead of hard-coding a heading level.
- Indent with 2 spaces.

More patterns, such as modifiers, lists and nested components, are in [references/twig-patterns.md](references/twig-patterns.md).

## 5. Add CSS and JavaScript

Drupal attaches `<name>.css` and `<name>.js` from the component directory as the library `core/components.star_wars--<name>` whenever the component renders. The theme's npm scripts don't process these files: `npm run build` compiles only `scss/` and `js/`, so write the component CSS as plain CSS, the way `button.css` does.

- Scope every selector to the component's root class, such as `.card` and `.card__heading`, and give interactive elements a visible `:focus-visible` style.
- Write JavaScript as a Drupal behavior wrapped in `once()`, so it runs once per element on page load and after AJAX:

```js
/**
 * @file
 * Card component behaviors.
 */

((Drupal, once) => {
  Drupal.behaviors.starWarsCard = {
    attach(context) {
      once('star-wars-theme-card', '.card', context).forEach((card) => {
        card.classList.add('card--enhanced');
      });
    },
  };
})(Drupal, once);
```

- Declare the libraries the script needs, such as `core/drupal` and `core/once`, under `libraryOverrides` in the schema, as [references/schema.md](references/schema.md) shows, so they load before the script.
- Styles that belong to the whole site go in `scss/` instead, and the theme build compiles them into `build/css/star_wars.min.css`. Rebuild with `ahoy fe` for production assets or `ahoy fed` for unminified assets with source maps, or keep `ahoy few` running to rebuild on every change.
- Run `ahoy fei` first when the theme's `node_modules` directory is missing.

## 6. Render the component

**From a Twig template, props only or slots passed as values.** Use `include()` with `with_context: false`, as `region--footer-bottom.html.twig` does, so the caller's variables don't leak into the component:

```twig
{{ include('star_wars:card', {heading: label, url: url, heading_level: 3, body: content.body}, with_context: false) }}
```

**From a Twig template, slots written as inline markup.** Use `embed` with `only` and override the slot blocks. `only` hides the caller's context from the blocks too, so pass every variable a block reads in the `with` hash:

```twig
{% embed 'star_wars:card' with {heading: label, url: url, body: content.body} only %}
  {% block body %}
    {{ body }}
    <a href="{{ url }}">{{ 'Read the full article'|t }}</a>
  {% endblock %}
{% endembed %}
```

**From PHP**, in a custom module or `star_wars.theme`, return a render array of type `component`:

```php
$build['card'] = [
  '#type' => 'component',
  '#component' => 'star_wars:card',
  '#props' => ['heading' => $node->label(), 'url' => $node->toUrl()->toString(), 'heading_level' => 3],
  '#slots' => ['body' => $node->get('body')->view(['type' => 'text_summary_or_trimmed', 'label' => 'hidden'])],
];
\Drupal\Core\Cache\CacheableMetadata::createFromObject($node)->applyTo($build['card']);
```

Drupal discovers theme components only for the active theme and its base themes, so rendering `star_wars:card` on a route that uses the administration theme fails. Keep such rendering on front-end routes.

## 7. Change an existing component

- Keep the existing prop and slot names. To rename one, update every caller found in step 1 in the same change.
- Add new props as optional, and handle their absence in the template with `|default()` or an `if`.
- Move `status` to `stable` once callers rely on the component, and to `deprecated` before removing it.

## 8. Check the work

1. Run `ahoy drush cr` after adding a component or changing a `*.component.yml` file, so Drupal discovers the change. Run it after template edits too, unless Twig development mode is on.
2. Open a page that renders the component. Get the site address from `ahoy info` and a login link from `ahoy login`. Confirm the markup, the classes from `attributes`, and that the component CSS and JS load.
3. Run `ahoy lint-fe` and fix what it reports. `ahoy lint-fe-fix` fixes formatting problems automatically.

`ahoy lint-fe` also runs Twig CS Fixer over the Twig files in `web/themes/custom/` and `web/modules/custom/`, configured in `.twig-cs-fixer.php`. See https://www.vortextemplate.com/docs/development/code-quality/twig-cs-fixer for the rules.

4. Validate the components with the SDC Devel module on a provisioned site:

   - `ahoy lint-sdc` validates every component in the theme and fails on critical issues, errors or warnings.
   - `ahoy drush sdc-devel:validate star_wars star_wars:card` validates 1 component.
   - Confirm that the output contains `No components errors found for`. The command also exits with code 0 when it validated nothing, such as for a theme with no components.
   - See https://www.vortextemplate.com/docs/development/code-quality/sdc-devel for how validation runs in CI.

## Report

Tell the developer which files you created or changed, the component ID, its props and slots, where it renders, and which checks passed. Link to https://www.vortextemplate.com/docs/development/themes for the theme's build and layout.
