# Vortex development

This directory holds the development files for Vortex itself. The Vortex
CLI removes it on install, and you can also delete it manually.

- [`docs`](docs) - documentation for Vortex, published to
  https://www.vortextemplate.com
- [`cli`](cli) - self-contained Symfony Console application that installs
  Vortex
- [`skills`](skills) - AI agent skills for maintaining Vortex, loaded by
  Claude Code as the `vortex` plugin
- [`tests`](tests) - unit and functional tests for Vortex
- [`tooling`](tooling) - scripts that ship with Vortex, published as the
  `drevops/vortex-tooling` package to
  https://github.com/drevops/vortex-tooling

The `ahoy` wrapper exposes the commands. Run `ahoy --help` to list them.
