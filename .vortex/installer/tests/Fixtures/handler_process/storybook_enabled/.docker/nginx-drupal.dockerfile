@@ -19,6 +19,8 @@
 
 COPY ./.docker/config/nginx/redirects-map.conf /etc/nginx/redirects-map.conf
 
+COPY ./.docker/config/nginx/storybook.conf /etc/nginx/conf.d/drupal/location_append-storybook.conf
+
 RUN fix-permissions /etc/nginx
 
 COPY --from=cli /app /app
