@@ -192,6 +192,32 @@
 - Add new props as optional, and handle their absence in the template with `|default()` or an `if`.
 - Move `status` to `stable` once callers rely on the component, and to `deprecated` before removing it.
 
+## Write the stories
+
+Storybook documents the theme's components, and Drupal renders every story. Read the Storybook section of https://www.vortextemplate.com/docs/development/themes before changing the setup.
+
+Write the stories in Twig as `<name>.stories.twig` next to the component, modelled on `button.stories.twig`. Add 1 story per meaningful state, such as with and without the optional props:
+
+```twig
+{% stories card with {title: 'Components/Card'} %}
+
+  {% story default with {name: 'Default', args: {heading: 'Card heading'}} %}
+    {{ include('star_wars:card', {heading: heading}, with_context: false) }}
+  {% endstory %}
+
+  {% story linked with {name: 'Linked', args: {heading: 'Card heading', url: 'https://example.com'}} %}
+    {{ include('star_wars:card', {heading: heading, url: url}, with_context: false) }}
+  {% endstory %}
+
+{% endstories %}
+```
+
+- Update the stories whenever the component's props or slots change.
+- `ahoy storybook-stories` compiles the Twig stories into `<name>.stories.json`. Git ignores the compiled files, so never commit them.
+- `ahoy storybook` runs the development server and regenerates the stories while it runs. It keeps running until stopped, so start it with `run_in_background` and read the address it prints.
+- `ahoy storybook-build` rebuilds the static library served at `/storybook`. Run it after adding, removing or renaming a story or changing its arguments.
+- In the `local` environment, provisioning turns on Twig development mode, so template edits show on the next render without a cache rebuild.
+
 ## 8. Check the work
 
 1. Run `ahoy drush cr` after adding a component or changing a `*.component.yml` file, so Drupal discovers the change. Run it after template edits too, unless Twig development mode is on.
@@ -199,13 +225,6 @@
 3. Run `ahoy lint-fe` and fix what it reports. `ahoy lint-fe-fix` fixes formatting problems automatically.
 
 `ahoy lint-fe` also runs Twig CS Fixer over the Twig files in `web/themes/custom/` and `web/modules/custom/`, configured in `.twig-cs-fixer.php`. See https://www.vortextemplate.com/docs/development/code-quality/twig-cs-fixer for the rules.
-
-4. Validate the components with the SDC Devel module on a provisioned site:
-
-   - `ahoy lint-sdc` validates every component in the theme and fails on critical issues, errors or warnings.
-   - `ahoy drush sdc-devel:validate star_wars star_wars:card` validates 1 component.
-   - Confirm that the output contains `No components errors found for`. The command also exits with code 0 when it validated nothing, such as for a theme with no components.
-   - See https://www.vortextemplate.com/docs/development/code-quality/sdc-devel for how validation runs in CI.
 
 ## Report
 
