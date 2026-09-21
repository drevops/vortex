@@ -22,3 +22,13 @@
 | `npm run watch`     | Watch for changes and rebuild automatically    |
 | `npm run lint`      | Check code style (JS and SCSS)                 |
 | `npm run lint-fix`  | Fix code style issues automatically            |
+
+## Storybook
+
+| Command                   | Description                             |
+|---------------------------|-----------------------------------------|
+| `npm run storybook`       | Start the Storybook development server  |
+| `npm run storybook-build` | Build the static Storybook application  |
+
+Stories are authored in Twig as `<component>.stories.twig` and compiled into
+`<component>.stories.json` by `drush storybook:generate-all-stories`.
