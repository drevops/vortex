---
title: Introduction
---

<h1 class="hero-title">
  <img src="assets/logo-black.png#only-light" alt="DrevOps Logo" />
  <img src="assets/logo-white.png#only-dark" alt="DrevOps Logo" />
  <span>DrevOps<sup>&reg;</sup></span>
</h1>

<h3 align="center"><em>Onboarding in minutes, not hours or days! 🚀🚀🚀</em></h3>

DrevOps is a project template for [Drupal](https://drupal.org) that is designed
to streamline the development process for building high-quality Drupal websites.

The template is validated through automated tests, ensuring all tooling and
workflows work correctly together.

## Main features

- [Drupal project scaffold](drupal) based
  on [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project)
- [Tools](tools) for code quality and automated testing
- [Production-grade Docker images](tools/docker) based
  on [Lagoon Docker images](https://github.com/uselagoon/lagoon-images)
- [Continuous integration](integrations/ci) configuration
- [Hosting integration](integrations/hosting)
- [Unified developer experience](workflows)

Refer to [Features](introduction/features.md) for more details.

## Why DrevOps?

There's no shortage of tools and workflows for Drupal. But piecing them all
together efficiently? That's a challenge. Even for those well-versed in the
ecosystem, integrating these tools seamlessly demands time and effort. It's like
fitting puzzle pieces together when some don't quite match.

Starting a new project by copying an older one might seem like a shortcut, but
it's not without its pitfalls. It's challenging to keep this "cloned" project in
sync with its predecessor, and more often than not, you'll find yourself
inheriting the same bugs.

Enter DrevOps. We've created a project template that eliminates the guesswork.
All the essential tools and workflows are already in place, fine-tuned, and
vetted through rigorous automated tests. And rest assured, the template itself
undergoes the same scrutiny as any subsequent projects based on it.

We've also ensured that tools and workflows can be tested independently, away
from high-stakes production projects. It’s a proactive approach to catch
potential regressions early on.

Lastly, with DrevOps, consistency is key. We aim for a smooth developer
experience across multiple projects, ensuring you're familiar with the structure
and documentation from one project to the next. It's about making things
efficient without compromising on quality.

## Quick start

    curl -SsL https://install.drevops.com | php

Refer to [Installation](introduction/installation.md) for more details.

## Contributing

Refer to [Contributing](contributing/README.md) for more details.

## License

DrevOps project template is licensed under the GPL-3.0 license. See
the [LICENSE](../../../../LICENSE) file for details.
