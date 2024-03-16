---
title: Introduction
---

<h1 class="hero-title">
  <img src="assets/logo-black.png#only-light" alt="DrevOps Logo" />
  <img src="assets/logo-white.png#only-dark" alt="DrevOps Logo" />
  <span>DrevOps<sup>&reg;</sup></span>
</h1>

DrevOps is a project scaffold for [Drupal](https://drupal.org) that is designed
to streamline the development process for building high-quality Drupal websites.

The template is validated through automated tests, ensuring all tooling and
workflows work correctly together.

It is designed to provide consistency across multiple projects, making it easier
for developers to switch between projects and get up to speed quickly.

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

There are many ways to build a Drupal website. The ecosystem is rich with
options, but there are not many places to find all the tools and workflows
pre-configured, tested and ready to go.

When starting a new project, you might consider copying and renaming some files
an existing project. It might seem like a good and fast shortcut, but it's not
without its pitfalls: it may become challenging to keep this "cloned" project
in sync with its  predecessor, and more often than not, you'll find yourself
inheriting the same configuration or workflow bugs. Then, when new tools may
need to be introduced, you'll have to manually add them to the project, which
can be time-consuming and error-prone.

Enter DrevOps.

We've created a project template that eliminates the guesswork: all the
essential tools and workflows are already in place, fine-tuned, and vetted
through rigorous automated tests.

Using DrevOps also provides a place to test workflows and tools independently,
away from high-stakes production projects. It’s a proactive approach to catch
potential regressions early on.

With DrevOps, consistency is key. We aim for a smooth developer experience
across multiple projects, ensuring you're familiar with the structure
and documentation from one project to the next. It's about making things
efficient without compromising on quality.

Lastly, we are not trying to reinvent the wheel. Instead, we are collecting the
best practices and tools from the community and making them work well together.

## Quick start

    curl -SsL https://install.drevops.com > install.php && php install.php; rm -r install.php

Refer to [Installation](introduction/installation.md) for more details.

!!!note

    We are currently looking at making the installation and update processes
    more user-friendly and support `composer create-project` command.

## Contributing

Refer to [Contributing](contributing) for more details.

## License

DrevOps project scaffold is licensed under the GPL-3.0 license. See
the [LICENSE](../../../../LICENSE) file for more details.
