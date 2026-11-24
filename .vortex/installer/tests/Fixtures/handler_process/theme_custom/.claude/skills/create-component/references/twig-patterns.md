@@ -1,6 +1,6 @@
 # Component Twig patterns
 
-Patterns for templates in `web/themes/custom/star_wars/components/`. Each example uses the theme's component IDs; adapt the names to the component at hand.
+Patterns for templates in `web/themes/custom/light_saber/components/`. Each example uses the theme's component IDs; adapt the names to the component at hand.
 
 ## Modifier classes from a prop
 
@@ -47,9 +47,9 @@
 {% set actions_output %}{% block actions %}{{ actions }}{% endblock %}{% endset %}
 
 {# The caller #}
-{% embed 'star_wars:card' with {heading: label, url: url} only %}
+{% embed 'light_saber:card' with {heading: label, url: url} only %}
   {% block actions %}
-    {{ include('star_wars:button', {label: 'Read more'|t, url: url}, with_context: false) }}
+    {{ include('light_saber:button', {label: 'Read more'|t, url: url}, with_context: false) }}
   {% endblock %}
 {% endembed %}
 ```
@@ -88,7 +88,7 @@
 ```php
 $build['card'] = [
   '#type' => 'component',
-  '#component' => 'star_wars:card',
+  '#component' => 'light_saber:card',
   '#props' => ['heading' => $title],
   '#attributes' => ['class' => ['card--compact']],
 ];
