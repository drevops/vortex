@@ -200,13 +200,6 @@
 
 `ahoy lint-fe` also runs Twig CS Fixer over the Twig files in `web/themes/custom/` and `web/modules/custom/`, configured in `.twig-cs-fixer.php`. See https://www.vortextemplate.com/docs/development/code-quality/twig-cs-fixer for the rules.
 
-4. Validate the components with the SDC Devel module on a provisioned site:
-
-   - `ahoy lint-sdc` validates every component in the theme and fails on critical issues, errors or warnings.
-   - `ahoy drush sdc-devel:validate star_wars star_wars:card` validates 1 component.
-   - Confirm that the output contains `No components errors found for`. The command also exits with code 0 when it validated nothing, such as for a theme with no components.
-   - See https://www.vortextemplate.com/docs/development/code-quality/sdc-devel for how validation runs in CI.
-
 ## Report
 
 Tell the developer which files you created or changed, the component ID, its props and slots, where it renders, and which checks passed. Link to https://www.vortextemplate.com/docs/development/themes for the theme's build and layout.
