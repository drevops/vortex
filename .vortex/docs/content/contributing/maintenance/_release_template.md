## [VERSION] - [SHORT TITLE]

[Very short summary, 1-3 sentences. E.g. "This release updates the base template to Drupal X.Y, improves the installer UX, and expands documentation for local dev."]

---

## 🔍 Highlights

- [1-3 top-level items that matter most to users]
- [Optional: link to detailed docs if relevant]

---

## 💥 Breaking changes

- [Describe any breaking changes and upgrade steps]
- [If none] None.

---

## What's new since [PREVIOUS_VERSION]

### 🌀 Template

- ✨ **New**
  - [New features or major additions in the project template]

- 🛠 **Changed**
  - [Improvements, refactors, behavior changes (but not fully breaking)]

- 🐞 **Fixed**
  - [Bug fixes]

- ⬆️ **Updated**
  - [Dependency bumps, version updates, etc.]

---

### 🧰 Tooling

- [Changes to the `drevops/vortex-tooling` scripts package, with the tag released with this version]
- [If none] None.

---

### 🎛 Installer

- ✨ **New**
  - [New installer options, flows, flags]

- 🛠 **Changed**
  - [Improved UX, default choices, messages]

- 🐞 **Fixed**
  - [Installer bugs, edge cases]

---

### 📖 Documentation

- ✨ **New**
  - [New pages, guides, sections]

- 🛠 **Changed**
  - [Rewrites, restructuring, clarifications]

- 🐞 **Fixed**
  - [Typos, incorrect examples, broken links]

---

## 📋 Release checklist

- [ ] Updated all dependencies outside of the schedule.
- [ ] Updated container images to the latest versions and checked that `@see` links are working.
- [ ] Updated PHP version in `composer.json` for `config.platform`.
- [ ] Updated PHP version in `phpcs.xml` for `testVersion`.
- [ ] Updated PHP version in `phpstan.neon` for `phpVersion`.
- [ ] Tagged `drevops/vortex-tooling` before the Vortex tag when the tooling changed, and pinned the freshly tagged version as the upper boundary in `composer.json`.
- [ ] Updated minor version of all packages in `composer.json`.
- [ ] Updated minor version of dependencies in theme's `package.json`.
- [ ] Updated `drevops/ci-runner` to the latest version in `.circleci/config.yml` and `.github/workflows/build-test-deploy.yml`.
- [ ] Incremented the database cache version in `.circleci/config.yml` and `.github/workflows/build-test-deploy.yml`.
- [ ] Updated documentation.
- [ ] Regenerated the installer snapshots.
- [ ] Re-recorded the demo videos.
- [ ] Tagged the Vortex release.

---

**Full Changelog**: https://github.com/drevops/vortex/compare/[PREVIOUS_VERSION]...[NEW_VERSION]

[CONTRIBUTORS]
