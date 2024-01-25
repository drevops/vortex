# Authoring documentation

There are 2 types of the documentation that DrevOps provides:

1. This Documentation of DrevOps that is then deployed
   to https://docs.drevops.com/
2. Consumer site documentation that is distributed when DrevOps is installed.

## docs.drevops.com

The Documentation (this site) is written in Markdown and located in
[`.drevops/docs`](../../../../.drevops/docs) directory. This is
removed when you install DrevOps for a
consumer site.

### Local build

```bash
cd .drevops/docs
ahoy build
```

Parts of the documentation is generated automatically from the codebase.
To update it, run:

```bash
composer -d .drevops/docs/.utils install
cd .drevops/docs
ahoy update
```

If you have the documentation site running locally, the updates will be seen
immediately.

### Check spelling and links

```bash
cd .drevops/docs
ahoy test
```

If required, add spelling exclusions to `.drevops/tests/.aspell.en.pws`
file.

### Publishing

An automated CI build publishes this documentation to https://docs.drevops.com/:
- on DrevOps release as a tag number
- on every push to `main` branch as `canary`
- on every commit to a branch that has `docs` string as a safe branch name

## Consumer site documentation

DrevOps provides a scaffold of the consumer site documentation in the
[`docs`](../../../../docs) directory.

After DrevOps is installed into the consumer site, these docs are intended to
be used by the site maintainers and stay up-to-date with the project changes.

See [Documentation](../../workflows/documentation.md) section.
