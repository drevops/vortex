# DrevOps documentation

This repository contains the documentation for the DrevOps project.

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

### Check spelling and links

```bash
cd scripts/drevops/docs
ahoy test
```
