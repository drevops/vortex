@@ -22,3 +22,31 @@
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
+In the project, `ahoy storybook` and `ahoy storybook-build` run these scripts
+inside the CLI container, with the dependencies installed for it.
+
+To run Storybook on the host instead, reinstall the dependencies for the host,
+keep the stories regenerating in the container, and set `STORYBOOK_SERVER_URL`
+to the site URL shown by `ahoy info`:
+
+```bash
+rm -rf node_modules && npm ci
+ahoy storybook-stories --watch # In a second terminal.
+STORYBOOK_SERVER_URL=http://<site-url> npm run storybook
+```
+
+Run `ahoy fei` before using the `ahoy` front-end commands again.
+
+Stories are authored in Twig as `<component>.stories.twig` and compiled into
+`<component>.stories.json` by
+`drush storybook:generate-all-stories --omit-server-url`. Without the option,
+each compiled story carries the Drush site URL and overrides the render
+endpoint resolved by `.storybook/preview.js`.
