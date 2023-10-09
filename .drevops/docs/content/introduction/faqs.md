# FAQs

## Why would I use DrevOps instead of just using Drupal Composer template?

You can use Drupal Composer template, but that will be your step 1. Your next
step would be to add all the missing pieces that DrevOps provides, such as CI
integrations, tool configuration, workflows, deployment scripts,
hosting-specific configurations, documentation.

Then you would have to test all of that and make sure that it works together.
And that there are no false positives (passing CI on broken tests can go
unnoticed up until the release and would block it).

## Can I use DrevOps with my existing project?

Yes, you can install DrevOps on top of your existing project.

## Can I use DrevOps with my existing project and keep my existing CI?

Yes, but you would need to update your CI configuration to use DrevOps'
workflow scripts.

We are in the process of adding support for other popular CI providers.

## Can I use DrevOps with my existing project and keep my existing hosting?

Yes, but you would need to update some of the deployment scripts to match your
hosting provider.

## What about BLT?

BLT uses a different approach to building Drupal projects, where your project
becomes dependent on BLT. DrevOps is a project template, which means that you
can override anything and everything in it. The code is all yours.

## I do not like some of the decisions made in DrevOps. Can I change them?

Yes, you can change anything you want. DrevOps is a project template, which
means that you can disable/remove certain features.

## I do not need all these features? It looks like an overkill.

One of the core principles of DrevOps is to provide
rich features to all projects, regardless of their size.

If you think that some of the features are not required for your project, then
DrevOps is not for you. However, there were cases when people have discovered
some new tools and approaches by using DrevOps and have decided to keep them
in their projects. So, you may want to give it a try.

## I think it is too opinionated.

DrevOps is opinionated to the point to make it work as a template. There are
some changes that you can make to the project, but there are also some that have
to stay in order to make it work.

There could be some cases when it is unreasonably opinionated - we consider this
as a bug and would be happy to fix it. Please provide your feedback in the
[issue queue](https://github.com/drevops/drevops/issues).

## How easy is it to upgrade DrevOps?

We provide a command to update DrevOps to the latest version. However, any
changes and adjustments made for your project would need to be manually resolved.

This is a trade-off between having an upgradable project template (where you
have full control) and a dependency package (where someone else has full control
of the code that drives your project).

## How easy is it choose the features that I need?

DrevOps provides an installer that allows you to choose the features that you
need interactively.

It is usually used as a part of the project creation process.

We are working on providing a more robust installer and a web-UI version.

## Why CircleCI? What about other CI providers?

CircleCI was chosen because of its flexibility. More specifically, CircleCI
allows to use your own runner container (the container where CI steps run) and
use remote Docker, making it possible to build the project in CI **exactly** the
same way as it would be built on the hosting (if your hosting uses Docker).

The other reason is that CircleCI supports passing the build artifacts between
jobs via cache. This becomes really powerful on large consumer sites, where
building the project takes a long time. DrevOps mitigates this by providing
a CI configuration to download and cache the database overnight and use this
cache for all the builds during the day, skipping the long database download
process.

And for the really large projects, CircleCI supports Docker image caching,
meaning that some parts of the application could be built once and then reused
in all the subsequent builds.

We are working on bringing integrations with other CI providers.
