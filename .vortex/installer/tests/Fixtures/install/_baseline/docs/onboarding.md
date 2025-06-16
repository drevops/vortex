Onboarding checklist
====================

Use this checklist to track the process of migration of the **existing site**
to Vortex. This file is intended to be committed into repository until
onboarding process is finished.

Put a `x` into `[ ]` if this step was executed OR not required - this will
indicate that it was addressed.

--------------------------------------------------------------------------------

## 1. Assessing current site

- [ ] Setup site on local machine using MAMP or DDEV to bootstrap the project.
- [ ] Install [hacked](https://www.drupal.org/project/hacked) module and extract
  a list of all modules with their versions. Add them below:
  ```
    Add a list of extracted modules here.
  ```

- [ ] Find existing patches or create new patches for all "hacked" modules. List
  them below:
  ```
  ctools: https:/drupal.org/path/to/file.patch
  ```
- [ ] Assess if there are any libraries used in the project, find their
  versions together with download URLs, and list them below:
  ```
  ckeditor@4.3.2, https://www.ckeditor.com/archive/ckeditor_4.3.2.zip
  ```

## 2. Adding Vortex

- [ ] Create a new GitHub repository, if required:
  - [ ] Commit generic `README.md` file and push to `master` branch.
  - [ ] Create new branch `ci`, copy all files from existing repository and
    push to remote.
- [ ] Add Vortex configuration using installer script and follow
  instructions in `README.md` file added to your project. You will need to
  commit some files and push them to remote. Note: try to rely on the
  default configuration provided by Vortex as much as possible
  (otherwise you are assuming maintenance responsibility for this custom
  code).
- [ ] Using list of **modules** from "Assessing current site" step, update
  provided `composer.json` with all required modules and patches. Ensure that
  `composer.lock` is updated and committed.
- [ ] Using list of **libraries** from "Assessing current site" step, update
  provided `composer.json` with all required libraries and patches. Make
  sure that `composer.lock` is updated and committed.
- [ ] Copy values from existing `settings.php` to provided `settings.php` file.
  Do not simply copy this file over! Transfer values one-by-one instead.
- [ ] Update values in `.env`:
  - [ ] Origin URL for [stage_file_proxy](https://www.drupal.org/project/stage_file_proxy)
    module.
- [ ] Copy values from existing `services.yml` to provided `services.yml` file.
  Do not simply copy this file over! Transfer values one-by-one instead.
- [ ] Assess existing `robots.txt` file and compare it with provided one. If
  they are different - commit existing `robots.txt` file.
- [ ] Assess existing `.htaccess` file and compare it with provided one. If
  they are different - commit existing `.htaccess` file.
- [ ] Update values in `.env` to reflect your project requirements. Note that
  in most cases no modification is required.
- [ ] Refactor front-end asset generation to use provided `Gruntfile.js`, if needed.
- [ ] Setup database download method depending on your requirements.
- [ ] Run `ahoy build` locally and ensure that the site can be bootstrapped
  and accessed in the browser.

## 3. Setting up CI

- [ ] If using CircleCI, login using your GitHub account and add this project.
- [ ] Depending on your database download method, add required private keys
  through UI. You will need to update key fingerprint in
  [CI configuration file](.circleci/config.yml).
- [ ] Add deployment variables through UI - see comments in
  [CI configuration file](.circleci/config.yml).
- [ ] Get the badge code (you may need to create access token in CI - read UI
  messages) and paste into your `README.md` file.
- [ ] Run successful build (all jobs must pass).

## 4. Setting up integrations

- [ ] Configure Renovate by [logging in](https://developer.mend.io/) with your GitHub account and
  adding a project through UI.

## 5. Cleanup

- [ ] Cleanup code or set one or more `VORTEX_CI_*_IGNORE_FAILURE` variables to
      `1` in your CI provider to temporary bypass code linting fails:
  - [ ] Cleanup PHP code
  - [ ] Cleanup JS code
  - [ ] Cleanup SCSS code
- [ ] Move custom functionality into `your_module_base` module's relevant
  inclusion files.
- [ ] Refactor modules functions to follow
  [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself),
  [KISS](https://en.wikipedia.org/wiki/KISS_principle) and
  [SRP](https://en.wikipedia.org/wiki/Single_responsibility_principle)
  principles.
- [ ] Refactor theme functions to follow
  [DRY](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself),
  [KISS](https://en.wikipedia.org/wiki/KISS_principle) and
  [SRP](https://en.wikipedia.org/wiki/Single_responsibility_principle)
  principles.

## 6. Validation

- [ ] Check that installed modules are the same version as initial modules and
  fix discrepancies.
- [ ] Check that deployment documentation has correct information.
- [ ] Perform visual regression testing:
  - [ ] Deploy a copy of the existing project database and code to a UAT
    environment.
  - [ ] Deploy a copy of the new project codebase along with the existing
    database to another available environment.
  - [ ] Add project to visual regression tool and configure exclusions for
    animated parts of the website.
  - [ ] Run visual regression and fix discrepancies.

## 7. Deployment

- [ ] Submit PR and include the contents of this file.
- [ ] Schedule deployment window with the Client and add the information below:
  ```
  Deployment approved by Jane Doe (jane.doe@example.com) on 2019/4/27 at 17:00
  via email to take place on 2019/4/29 at 18:30.
  ```
- [ ] Get PR approval (do not merge yet!). You may need to wait for deployment
  window before merging (depends on the type of the deployment integration).
- [ ] Merge PR and ensure that CI passed.
- [ ] Create `develop` branch.
- [ ] Create a new release, push to remote and ensure that CI passed.
- [ ] Deploy new release to production.
- [ ] Notify stakeholders about deployment and ask for spot-checking.
- [ ] Receive confirmation that deployment was successful.
- [ ] Set `develop` branch as default in GitHub.
- [ ] Set branch protection in GitHub for `develop` branch.
- [ ] Set branch protection in GitHub for `master` branch.

--------------------------------------------------------------------------------

Once all boxes above are checked, remove this file from the repository
and remove the reference to it from the `README.md` file.
