# Vortex Documentation Development

Documentation built with [Docusaurus](https://docusaurus.io/) and React components.

## Quick Start

```bash
yarn install           # Install dependencies
yarn start            # Start dev server
yarn build            # Build for production
```

## Development Commands

```bash
# Development
yarn start            # Hot reloading dev server
yarn build            # Production build
yarn serve            # Serve built site locally

# Testing
yarn test             # Run all tests
yarn test:unit        # Unit tests only
yarn test:unit:watch  # Unit tests in watch mode
yarn test:e2e         # End-to-end tests

# Quality
yarn lint             # Check code quality
yarn lint-fix         # Auto-fix issues
yarn spellcheck       # American English validation
```

## Project Structure

```
docs/
├── content/                # MDX documentation files
├── src/components/         # React components (VerticalTabs, etc.)
├── tests/
│   ├── unit/              # Jest unit tests
│   └── e2e/               # Puppeteer E2E tests
├── jest.config.js         # Test configuration
└── cspell.json           # Spellcheck configuration
```

## Component Usage

### VerticalTabs

```jsx
import { VerticalTabs, VerticalTab, VerticalTabPanel } from '@site/src/components/VerticalTabs';

<VerticalTabs>
  <VerticalTab>💧 Title | Subtitle description</VerticalTab>
  <VerticalTabPanel>
    Content for this tab...
  </VerticalTabPanel>
</VerticalTabs>
```

## Writing Tests

**Component Tests** (`tests/unit/`):
```javascript
import { render, screen } from '@testing-library/react';

test('component renders correctly', () => {
  render(<Component />);
  expect(screen.getByText('Expected text')).toBeInTheDocument();
});
```

## Content Guidelines

- **American English**: Use American spelling (organize, color, behavior)
- **MDX Support**: Combine Markdown with React components
- **Code Examples**: Include practical bash/JavaScript examples

## Pre-commit Checklist

```bash
yarn lint && yarn spellcheck && yarn test && yarn build
```

- [ ] Tests pass
- [ ] Build succeeds  
- [ ] No lint errors
- [ ] Spellcheck passes