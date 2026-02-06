# Documentation System Guide

## Overview

Docusaurus-based documentation website published to https://www.vortextemplate.com

**Technology**: Docusaurus, React, MDX, Jest, cspell

## Commands

```bash
cd .vortex/docs

yarn install      # Install dependencies
yarn start        # Development server
yarn build        # Production build
yarn test         # Run Jest tests
yarn spellcheck   # American English validation
yarn lint         # Code quality checks
yarn lint-fix     # Auto-fix issues
```

## Key Directories

```
docs/
├── content/           # MDX documentation pages
├── src/components/    # React components
├── tests/unit/        # Jest tests
├── sidebars.js        # Sidebar configuration
└── docusaurus.config.js
```

## Writing Guidelines

- **American English** spelling throughout
- **Sentence case** for headings (capitalize only first letter + proper nouns)
- Proper nouns: Vortex, GitHub, Drupal, Docker Compose, CircleCI
- Acronyms: CI/CD, SSH, API, BDD, PHPUnit

## Sidebar Configuration

Categories defined in `sidebars.js`. For subdirectories:
- Category label from explicit definition or directory name
- Use `sidebar_label: Overview` in README.mdx for first item

## Troubleshooting

```bash
# Build failures
yarn build --verbose

# Spellcheck failures
yarn spellcheck
npx cspell "content/**/*.md"

# Test failures
yarn test --verbose
yarn test --updateSnapshot
```
