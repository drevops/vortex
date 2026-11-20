@@ -198,8 +198,6 @@
 2. Open a page that renders the component. Get the site address from `ahoy info` and a login link from `ahoy login`. Confirm the markup, the classes from `attributes`, and that the component CSS and JS load.
 3. Run `ahoy lint-fe` and fix what it reports. `ahoy lint-fe-fix` fixes formatting problems automatically.
 
-`ahoy lint-fe` also runs Twig CS Fixer over the Twig files in `web/themes/custom/` and `web/modules/custom/`, configured in `.twig-cs-fixer.php`. See https://www.vortextemplate.com/docs/development/code-quality/twig-cs-fixer for the rules.
-
 4. Validate the components with the SDC Devel module on a provisioned site:
 
    - `ahoy lint-sdc` validates every component in the theme and fails on critical issues, errors or warnings.
