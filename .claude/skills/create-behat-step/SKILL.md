---
name: create-behat-step
description: Create a Behat step of a project's own on the public helpers of drevops/behat-steps 4.x, as a method in a trait of the project's that its FeatureContext uses. Takes a plain-English description of what the step should do. Use when asked to add, write or create a custom Behat step, step definition or domain step, or when a scenario needs a step that no registered step provides, in a project that requires drevops/behat-steps.
---

# Create a Behat step

The `drevops/behat-steps` package ships this skill, so its instructions
match the release of the package that the project installs.

Read `vendor/drevops/behat-steps/skills/create-behat-step/SKILL.md` and
follow it, with the description this skill was invoked with.

When that file is missing:

1. Run `ahoy composer install`. When the command fails, stop and report
   its error to the user.
2. Read the file again.
3. When it is still missing, run `ahoy composer show drevops/behat-steps`.
   For a release before 4.0, tell the user that this skill needs 4.x.
   For any other result, report the version and the missing path. Then
   stop.
