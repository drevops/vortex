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
