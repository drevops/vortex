@@ -22,6 +22,8 @@
 COPY ./.docker/config/nginx/maintenance.conf /etc/nginx/conf.d/drupal/server_prepend-maintenance.conf
 COPY ./.docker/config/nginx/maintenance.html /etc/nginx/html/maintenance.html
 
+COPY ./.docker/config/nginx/storybook.conf /etc/nginx/conf.d/drupal/location_append-storybook.conf
+
 RUN fix-permissions /etc/nginx
 
 COPY --from=cli /app /app
