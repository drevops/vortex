@@ -5,7 +5,7 @@
 
 # Create a component
 
-Build Single Directory Components in the custom theme `star_wars` the way the theme's own sample component is built. The theme lives in `web/themes/custom/star_wars/` and its components live in `web/themes/custom/star_wars/components/<name>/`.
+Build Single Directory Components in the custom theme `lightsaber` the way the theme's own sample component is built. The theme lives in `web/themes/custom/lightsaber/` and its components live in `web/themes/custom/lightsaber/components/<name>/`.
 
 Run every command from the project root. Every command goes through `ahoy`, 1 command per Bash call. Never run `npm`, `drush` or `composer` on the host.
 
@@ -12,9 +12,9 @@
 ## 1. Read before writing
 
 1. Read the theme documentation in `.vortex/docs/content/development/themes.mdx`, published at https://www.vortextemplate.com/docs/development/themes. It covers the build commands, the file layout and the sample component. Don't restate it to the developer; link to it.
-2. Read every file in `web/themes/custom/star_wars/components/button/`. It is the model for naming, schema style, template comments and CSS. Match it.
-3. Read `web/themes/custom/star_wars/templates/layout/region--footer-bottom.html.twig`, which renders the button from a regular template.
-4. For a change to an existing component, search the codebase for its ID, such as `star_wars:button`, in `web/themes/custom/` and `web/modules/custom/`. List every caller before changing a prop or slot.
+2. Read every file in `web/themes/custom/lightsaber/components/button/`. It is the model for naming, schema style, template comments and CSS. Match it.
+3. Read `web/themes/custom/lightsaber/templates/layout/region--footer-bottom.html.twig`, which renders the button from a regular template.
+4. For a change to an existing component, search the codebase for its ID, such as `lightsaber:button`, in `web/themes/custom/` and `web/modules/custom/`. List every caller before changing a prop or slot.
 
 ## 2. Decide props and slots
 
@@ -28,7 +28,7 @@
 
 ## 3. Create the directory and the schema
 
-The directory name, the `*.component.yml` base name and the `*.twig` base name are identical. The component ID is `star_wars:<name>`.
+The directory name, the `*.component.yml` base name and the `*.twig` base name are identical. The component ID is `lightsaber:<name>`.
 
 ```text
 components/card/
@@ -127,7 +127,7 @@
 
 ## 5. Add CSS and JavaScript
 
-Drupal attaches `<name>.css` and `<name>.js` from the component directory as the library `core/components.star_wars--<name>` whenever the component renders. The theme's npm scripts don't process these files: `npm run build` compiles only `scss/` and `js/`, so write the component CSS as plain CSS, the way `button.css` does.
+Drupal attaches `<name>.css` and `<name>.js` from the component directory as the library `core/components.lightsaber--<name>` whenever the component renders. The theme's npm scripts don't process these files: `npm run build` compiles only `scss/` and `js/`, so write the component CSS as plain CSS, the way `button.css` does.
 
 - Scope every selector to the component's root class, such as `.card` and `.card__heading`, and give interactive elements a visible `:focus-visible` style.
 - Write JavaScript as a Drupal behavior wrapped in `once()`, so it runs once per element on page load and after AJAX:
@@ -139,9 +139,9 @@
  */
 
 ((Drupal, once) => {
-  Drupal.behaviors.starWarsCard = {
+  Drupal.behaviors.lightsaberCard = {
     attach(context) {
-      once('star-wars-theme-card', '.card', context).forEach((card) => {
+      once('the-new-hope-theme-card', '.card', context).forEach((card) => {
         card.classList.add('card--enhanced');
       });
     },
@@ -150,7 +150,7 @@
 ```
 
 - Declare the libraries the script needs, such as `core/drupal` and `core/once`, under `libraryOverrides` in the schema, as [references/schema.md](references/schema.md) shows, so they load before the script.
-- Styles that belong to the whole site go in `scss/` instead, and the theme build compiles them into `build/css/star_wars.min.css`. Rebuild with `ahoy fe` for production assets or `ahoy fed` for unminified assets with source maps, or keep `ahoy few` running to rebuild on every change.
+- Styles that belong to the whole site go in `scss/` instead, and the theme build compiles them into `build/css/lightsaber.min.css`. Rebuild with `ahoy fe` for production assets or `ahoy fed` for unminified assets with source maps, or keep `ahoy few` running to rebuild on every change.
 - Run `ahoy fei` first when the theme's `node_modules` directory is missing.
 
 ## 6. Render the component
@@ -158,13 +158,13 @@
 **From a Twig template, props only or slots passed as values.** Use `include()` with `with_context: false`, as `region--footer-bottom.html.twig` does, so the caller's variables don't leak into the component:
 
 ```twig
-{{ include('star_wars:card', {heading: label, url: url, heading_level: 3, body: content.body}, with_context: false) }}
+{{ include('lightsaber:card', {heading: label, url: url, heading_level: 3, body: content.body}, with_context: false) }}
 ```
 
 **From a Twig template, slots written as inline markup.** Use `embed` with `only` and override the slot blocks. `only` hides the caller's context from the blocks too, so pass every variable a block reads in the `with` hash:
 
 ```twig
-{% embed 'star_wars:card' with {heading: label, url: url, body: content.body} only %}
+{% embed 'lightsaber:card' with {heading: label, url: url, body: content.body} only %}
   {% block body %}
     {{ body }}
     <a href="{{ url }}">{{ 'Read the full article'|t }}</a>
@@ -172,12 +172,12 @@
 {% endembed %}
 ```
 
-**From PHP**, in a custom module or `star_wars.theme`, return a render array of type `component`:
+**From PHP**, in a custom module or `lightsaber.theme`, return a render array of type `component`:
 
 ```php
 $build['card'] = [
   '#type' => 'component',
-  '#component' => 'star_wars:card',
+  '#component' => 'lightsaber:card',
   '#props' => ['heading' => $node->label(), 'url' => $node->toUrl()->toString(), 'heading_level' => 3],
   '#slots' => ['body' => $node->get('body')->view(['type' => 'text_summary_or_trimmed', 'label' => 'hidden'])],
 ];
@@ -184,7 +184,7 @@
 \Drupal\Core\Cache\CacheableMetadata::createFromObject($node)->applyTo($build['card']);
 ```
 
-Drupal discovers theme components only for the active theme and its base themes, so rendering `star_wars:card` on a route that uses the administration theme fails. Keep such rendering on front-end routes.
+Drupal discovers theme components only for the active theme and its base themes, so rendering `lightsaber:card` on a route that uses the administration theme fails. Keep such rendering on front-end routes.
 
 ## 7. Change an existing component
 
@@ -203,7 +203,7 @@
 4. Validate the components with the SDC Devel module on a provisioned site:
 
    - `ahoy lint-sdc` validates every component in the theme and fails on critical issues, errors or warnings.
-   - `ahoy drush sdc-devel:validate star_wars star_wars:card` validates 1 component.
+   - `ahoy drush sdc-devel:validate lightsaber lightsaber:card` validates 1 component.
    - Confirm that the output contains `No components errors found for`. The command also exits with code 0 when it validated nothing, such as for a theme with no components.
    - See https://www.vortextemplate.com/docs/development/code-quality/sdc-devel for how validation runs in CI.
 
