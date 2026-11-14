@@ -1,6 +1,6 @@
 # Component schema reference
 
-Keys of a `*.component.yml` file in `web/themes/custom/star_wars/components/<name>/`. Keep every file in block YAML, like `button.component.yml`.
+Keys of a `*.component.yml` file in `web/themes/custom/lightsaber/components/<name>/`. Keep every file in block YAML, like `button.component.yml`.
 
 ## Top-level keys
 
@@ -56,7 +56,7 @@
 
 ## Library overrides
 
-Drupal builds the library `core/components.star_wars--<name>` from the component's CSS and JS files. Add dependencies and file options under `libraryOverrides`:
+Drupal builds the library `core/components.lightsaber--<name>` from the component's CSS and JS files. Add dependencies and file options under `libraryOverrides`:
 
 ```yaml
 libraryOverrides:
@@ -65,7 +65,7 @@
     - core/once
 ```
 
-To depend on another component's assets, list its library, such as `core/components.star_wars--button`. To load the assets of a component from a template that doesn't render it, call `{{ attach_library('core/components.star_wars--card') }}`.
+To depend on another component's assets, list its library, such as `core/components.lightsaber--button`. To load the assets of a component from a template that doesn't render it, call `{{ attach_library('core/components.lightsaber--card') }}`.
 
 ## Variants
 
@@ -83,4 +83,4 @@
 
 ## Replacing a component
 
-Only a theme can replace a component. Add `replaces: '<provider>:<name>'` to the new component's schema. Both the original and the replacement need a `props` schema. To change only the CSS or JS of a component from another provider, use `libraries-override` in `star_wars.info.yml` instead.
+Only a theme can replace a component. Add `replaces: '<provider>:<name>'` to the new component's schema. Both the original and the replacement need a `props` schema. To change only the CSS or JS of a component from another provider, use `libraries-override` in `lightsaber.info.yml` instead.
