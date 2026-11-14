@@ -5,7 +5,7 @@
 
 # Create a component
 
-Build Single Directory Components in the custom theme `star_wars` the way the theme's own sample component is built. The theme lives in `web/themes/custom/star_wars/` and its components live in `web/themes/custom/star_wars/components/<name>/`.
+Build Single Directory Components in the custom theme `star_wars` the way the theme's own sample component is built. The theme lives in `docroot/themes/custom/star_wars/` and its components live in `docroot/themes/custom/star_wars/components/<name>/`.
 
 Run every command from the project root. Every command goes through `ahoy`, 1 command per Bash call. Never run `npm`, `drush` or `composer` on the host.
 
@@ -12,9 +12,9 @@
 ## 1. Read before writing
 
 1. Read the theme documentation in `.vortex/docs/content/development/themes.mdx`, published at https://www.vortextemplate.com/docs/development/themes. It covers the build commands, the file layout and the sample component. Don't restate it to the developer; link to it.
-2. Read every file in `web/themes/custom/star_wars/components/button/`. It is the model for naming, schema style, template comments and CSS. Match it.
-3. Read `web/themes/custom/star_wars/templates/layout/region--footer-bottom.html.twig`, which renders the button from a regular template.
-4. For a change to an existing component, search the codebase for its ID, such as `star_wars:button`, in `web/themes/custom/` and `web/modules/custom/`. List every caller before changing a prop or slot.
+2. Read every file in `docroot/themes/custom/star_wars/components/button/`. It is the model for naming, schema style, template comments and CSS. Match it.
+3. Read `docroot/themes/custom/star_wars/templates/layout/region--footer-bottom.html.twig`, which renders the button from a regular template.
+4. For a change to an existing component, search the codebase for its ID, such as `star_wars:button`, in `docroot/themes/custom/` and `docroot/modules/custom/`. List every caller before changing a prop or slot.
 
 ## 2. Decide props and slots
 
@@ -198,7 +198,7 @@
 2. Open a page that renders the component. Get the site address from `ahoy info` and a login link from `ahoy login`. Confirm the markup, the classes from `attributes`, and that the component CSS and JS load.
 3. Run `ahoy lint-fe` and fix what it reports. `ahoy lint-fe-fix` fixes formatting problems automatically.
 
-`ahoy lint-fe` also runs Twig CS Fixer over the Twig files in `web/themes/custom/` and `web/modules/custom/`, configured in `.twig-cs-fixer.php`. See https://www.vortextemplate.com/docs/development/code-quality/twig-cs-fixer for the rules.
+`ahoy lint-fe` also runs Twig CS Fixer over the Twig files in `docroot/themes/custom/` and `docroot/modules/custom/`, configured in `.twig-cs-fixer.php`. See https://www.vortextemplate.com/docs/development/code-quality/twig-cs-fixer for the rules.
 
 4. Validate the components with the SDC Devel module on a provisioned site:
 
