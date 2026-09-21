# Your Site Theme

Custom Drupal theme for the project.

## Requirements

- Node.js (LTS version recommended)
- npm

## Installation

```bash
npm ci
```

## Commands

| Command             | Description                                    |
|---------------------|------------------------------------------------|
| `npm run build`     | Production build (minified, no source maps)    |
| `npm run build-dev` | Development build (expanded, with source maps) |
| `npm run watch`     | Watch for changes and rebuild automatically    |
| `npm run lint`      | Check code style (JS and SCSS)                 |
| `npm run lint-fix`  | Fix code style issues automatically            |

[//]: # (#;< STORYBOOK)

## Storybook

| Command                   | Description                             |
|---------------------------|-----------------------------------------|
| `npm run storybook`       | Start the Storybook development server  |
| `npm run storybook-build` | Build the static Storybook application  |

Stories are authored in Twig as `<component>.stories.twig` and compiled into
`<component>.stories.json` by `drush storybook:generate-all-stories`.

[//]: # (#;> STORYBOOK)
