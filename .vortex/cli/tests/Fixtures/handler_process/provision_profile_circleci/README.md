@@ -9,7 +9,7 @@
 
 Drupal 11 implementation of star wars for star wars Org
 
-[![Build, Test and Deploy](https://github.com/star_wars_org/star_wars/actions/workflows/build-test-deploy.yml/badge.svg)](https://github.com/star_wars_org/star_wars/actions/workflows/build-test-deploy.yml)
+[![CircleCI](https://circleci.com/gh/star_wars_org/star_wars.svg?style=shield)](https://circleci.com/gh/star_wars_org/star_wars)
 
 ![Drupal 11](https://img.shields.io/badge/Drupal-11-0678BE?logo=drupal&logoColor=white)
 
@@ -32,8 +32,6 @@
 - Make sure that you have latest versions of all required software installed: [Docker](https://www.docker.com/), [Pygmy](https://github.com/pygmystack/pygmy), [Ahoy](https://github.com/ahoy-cli/ahoy)
 - Make sure that all local web development services are shut down (Apache/Nginx, Mysql, MAMP etc).
 - Checkout project repository (in one of the [supported Docker directories](https://docs.docker.com/desktop/settings-and-maintenance/settings/#virtual-file-shares)).
-
-- `ahoy fetch-db`
 
 - `pygmy up`
 - `ahoy build`
