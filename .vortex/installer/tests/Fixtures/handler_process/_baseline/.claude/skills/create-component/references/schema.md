# Component schema reference

Keys of a `*.component.yml` file in `web/themes/custom/star_wars/components/<name>/`. Keep every file in block YAML, like `button.component.yml`.

## Top-level keys

| Key                | Purpose                                                                                      |
|--------------------|----------------------------------------------------------------------------------------------|
| `$schema`          | Points editors at the metadata schema. Copy the line from `button.component.yml`.            |
| `name`             | Human-readable name shown in component listings.                                             |
| `status`           | 1 of `experimental`, `stable`, `deprecated` or `obsolete`.                                   |
| `description`      | 1 sentence on what the component renders.                                                    |
| `props`            | JSON Schema object that describes the props.                                                 |
| `slots`            | Map of slot names, each with a `title` and a `description`.                                  |
| `libraryOverrides` | Extra library settings merged into the component's generated library.                        |
| `variants`         | Named visual variants of the same markup. Available since Drupal 11.2.                       |
| `replaces`         | ID of another component that this one replaces, as `<provider>:<name>`.                      |

## Prop types

Each entry under `props.properties` takes a JSON Schema `type` plus a `title` and a `description`.

| Need                          | Schema                                                                 |
|-------------------------------|------------------------------------------------------------------------|
| Text                          | `type: string`                                                         |
| Whole number                  | `type: integer`, with `minimum` and `maximum` when the range is fixed |
| Fixed set of values           | `type: string` or `type: integer` with an `enum` list                  |
| On or off                     | `type: boolean`                                                        |
| List of plain values          | `type: array` with an `items` schema, such as `type: string`           |
| Structured value              | `type: object` with its own `properties`                               |
| Optional value that may be null | A list of types, such as `string` and `'null'`                       |

List required props under `props.required`. A prop that a template can't render without, such as the button's `label`, belongs there. Everything else stays optional and gets a fallback in the template with `|default()`.

Pass extra `Attribute` objects, for an inner element the caller must control, as a prop of type `Drupal\Core\Template\Attribute`:

```yaml
    link_attributes:
      type: Drupal\Core\Template\Attribute
      title: Link attributes
      description: Attributes for the heading link.
```

Never declare `attributes` itself. Drupal passes it to every component.

## Slots

```yaml
slots:
  body:
    title: Body
    description: Teaser content rendered below the heading.
```

A slot has no type. It accepts a render array, a markup object, a string or the output of another component.

## Library overrides

Drupal builds the library `core/components.star_wars--<name>` from the component's CSS and JS files. Add dependencies and file options under `libraryOverrides`:

```yaml
libraryOverrides:
  dependencies:
    - core/drupal
    - core/once
```

To depend on another component's assets, list its library, such as `core/components.star_wars--button`. To load the assets of a component from a template that doesn't render it, call `{{ attach_library('core/components.star_wars--card') }}`.

## Variants

Since Drupal 11.2, a component can declare variants that share 1 template:

```yaml
variants:
  default:
    title: Default
  secondary:
    title: Secondary
```

The template reads the selected name from the `variant` variable, and callers pass `variant: 'secondary'` to `include()` or `'#variant' => 'secondary'` in a render array. The sample button uses a `modifier` prop for the same purpose. Pick 1 approach per theme and keep to it.

## Replacing a component

Only a theme can replace a component. Add `replaces: '<provider>:<name>'` to the new component's schema. Both the original and the replacement need a `props` schema. To change only the CSS or JS of a component from another provider, use `libraries-override` in `star_wars.info.yml` instead.
