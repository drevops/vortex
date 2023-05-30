# Authoring documentation

There are 2 types of the documentation that DrevOps provides:
1. This Documentation of DrevOps that is then deployed to https://docs.drevops.com/
2. Consumer site documentation that is distributed when DrevOps is installed.

## docs.drevops.com

The Documentation (this site) is written in Markdown and stored in
`scripts/drevops/docs` directory. This is removed when you install DrevOps for a
consumer site.

### Local build

```bash
cd scripts/drevops/docs
ahoy build
```

Parts of the documentation is generated automatically from the codebase.
To update it, run:
```bash
composer -d scripts/drevops/docs/.utils install
cd scripts/drevops/docs
ahoy update
```
If you have the documentation site running locally, the updates will be seen
immediately.

### Check spelling

```bash
cd scripts/drevops/docs
ahoy lint
```

If the exclusion needs to be added, add it to `scripts/drevops/tests/.aspell.en.pws` file.

### Publishing

An automated CI build publishes this documentation on DrevOps release
(as a latest version) and on every commit to a branch with `docs` string in its
name (to allow publishing documentation for feature branches).

## Consumer site documentation

DrevOps provides a scaffold of the consumer site documentation in the
[`docs`](../../../../docs).

After DrevOps is installed into the consumer site, these docs are intended to
be used by the site maintainers and stay up-to-date with the project changes.
