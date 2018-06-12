##
# Build project dependncies.
#
# Usage:
# make <target>
#
# make help - show a list of available targets.
# make build - build project
#
include .env

.DEFAULT_GOAL := help
.PHONY: build build-artefact build-fed build-fed-prod clean clean-full cs db-import docker-cli docker-destroy docker-logs docker-pull docker-restart docker-start docker-stop drush help import-db install-site lint login rebuild rebuild-full site-install test test-behat

## Build project dependencies.
build:
	$(call title,Building project dependencies)
	$(call exec,$(MAKE) docker-start)
	$(call exec,composer install -n --ansi --prefer-dist --no-suggest)
	$(call exec,$(MAKE) build-fed)
	$(call exec,$(MAKE) import-db)
	@echo ''
	$(call title,Build complete)
	@echo ''
	@printf "${GREEN}Site URL              :${RESET} $(URL)\n"
	@printf "${GREEN}Path inside container :${RESET} $(APP)\n"
	@printf "${GREEN}Path to docroot       :${RESET} $(DOCROOT)\n"
	@printf "${GREEN}One-time login        :${RESET} " && docker-compose exec cli drush -r $(DOCROOT) -l $(URL) uli

## Build deployment artefact.
build-artefact:
	$(call title,Building deployment artefact)
	$(call exec,robo --ansi --load-from $(pwd)/vendor/integratedexperts/robo-git-artefact/RoboFile.php artefact --gitignore=.gitignore.artefact)

## Build front-end assets.
build-fed:
	$(call title,Building front-end assets)
	$(call exec,npm install)
	$(call exec,npm run build)

## Build front-end assets for production.
build-fed-prod:
	$(call title,Building front-end assets (production))
	$(call exec,npm install)
	$(call exec,npm run build-prod)

## Clear Drupal cache. Alias for 'clear-cache'.
cc: clear-cache

## Remove dependencies.
clean:
	$(call title,Removing dependencies)
	$(call exec,chmod -Rf 777 docroot/sites/default)
	$(call exec,git ls-files --directory --other -i --exclude-from=.gitignore $(WEBROOT)|xargs rm -Rf)
	$(call exec,rm -Rf vendor)
	$(call exec,rm -Rf node_modules)

## Remove dependencies and Docker images.
clean-full: clean docker-stop docker-destroy

## Clear Drupal cache.
clear-cache:
	$(call title,Clearing Drupal cache)
	$(call exec,docker-compose exec cli bash -c "if [ -e ./$(WEBROOT)/sites/default/services.yml ] ; then drush -r $(DOCROOT) cr -y; else drush -r $(DOCROOT) cc all; fi")

## Lint code. Alias for 'lint'.
cs: lint

## Import database. Alias for 'import-db'.
db-import: import-db

## Download database. Alias for 'download-db'.
db-download: download-db

## Execute command inside of CLI container.
docker-cli:
	$(call title,Executing command inside of CLI container)
	$(call exec,docker-compose exec cli $(filter-out $@,$(MAKECMDGOALS)))

## Destroy Docker containers.
docker-destroy:
	$(call title,Destroying Dockert containers)
	$(call exec,docker-compose down)

## Show logs.
docker-logs:
	$(call title,Displaying Docker logs)
	$(call exec,docker-compose logs)

## Pull newest base images.
docker-pull:
	$(call title,Pulling Docker containers)
	$(call exec,docker image ls --format \"{{.Repository}}:{{.Tag}}\" | grep $(DOCKER_IMAGE_PREFIX) | grep -v none | xargs -n1 docker pull | cat)

## Re-start Docker containers.
docker-restart:
	$(call title,Restarting Docker containers)
	$(call exec,docker-compose restart)

## Start Docker containers.
docker-start:
	$(call title,Starting Docker containers)
	$(call exec,COMPOSE_CONVERT_WINDOWS_PATHS=1 docker-compose up -d --build)

## Stop Docker containers.
docker-stop:
	$(call title,Stopping Docker containers)
	$(call exec,docker-compose stop)

## Download database.
download-db:
	$(call title,Downloading database)
	$(call exec,mkdir -p .data && curl -L $(DUMMY_DB) -o .data/db.sql)

## Run Drush command.
drush:
	$(call title,Executing Drush command inside CLI container)
	$(call exec,docker-compose exec cli drush -r $(DOCROOT) $(filter-out $@,$(MAKECMDGOALS)))

## Display this help message.
help:
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk '/^[a-zA-Z\-0-9][a-zA-Z\-\_0-9]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")-1); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf "  ${YELLOW}%-$(HELP_TARGET_WIDTH)s${RESET} ${GREEN}%s${RESET}\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)

## Import database.
import-db:
	$(call title,Importing database from the dump)
	$(call exec,docker-compose exec cli drush -r $(DOCROOT) sql-drop -y)
	$(call exec,docker-compose exec cli bash -c "drush -r $(DOCROOT) sqlc < /tmp/.data/db.sql")
	$(call exec,docker-compose exec cli drush -r $(DOCROOT) en mysite_core -y)
	$(call exec,docker-compose exec cli bash -c "if [ -e ./config/sync/*.yml ] ; then drush -r $(DOCROOT) -y cim; fi")
	$(call exec,docker-compose exec cli bash -c "if [ -e ./config/sync/*.yml ] ; then drush -r $(DOCROOT) -y cim; fi")
	$(call exec,$(MAKE) clear-cache)
	$(call exec,docker-compose exec cli bash -c "if [ -e ./config/sync/*.yml ] ; then drush -r $(DOCROOT) -n cim 2>&1 | grep -q 'There are no changes to import.'; fi")

## Install site. Alias for 'site-install'.
install-site: site-install

## Lint code.
lint:
	$(call title,Linting code)
	$(call exec,vendor/bin/parallel-lint --exclude vendor --exclude node_modules -e $(PHP_LINT_EXTENSIONS) $(PHP_LINT_TARGETS))
	$(call exec,vendor/bin/phpcs)
	$(call exec,npm run lint)

## Login to the website.
login:
	$(call title,Generating login link for user 1)
	$(call exec,docker-compose exec cli drush uublk 1)
	$(call exec,docker-compose exec cli drush uli -r $(DOCROOT) -l $(URL) | xargs open)

## Re-build project dependencies.
rebuild: clean build

## clean and fully re-build project dependencies.
rebuild-full: clean-full build

# Install site.
site-install:
	$(call title,Installing a site from profile)
	$(call exec,docker-compose exec cli drush -r $(DOCROOT) si mysite_profile -y --account-name=admin --account-pass=admin install_configure_form.enable_update_status_module=NULL install_configure_form.enable_update_status_emails=NULL)
	$(call exec,$(MAKE) clear-cache)

## Run all tests.
test: test-behat

## Run Behat tests.
test-behat:
	$(call title,Running behat tests)
	$(call exec,docker-compose exec cli vendor/bin/behat --format=progress_fail --colors $(BEHAT_PROFILE) $(filter-out $@,$(MAKECMDGOALS)))

#-------------------------------------------------------------------------------
# VARIABLES.
#-------------------------------------------------------------------------------

APP ?= /app
WEBROOT ?= web
DOCROOT ?= $(APP)/$(WEBROOT)
URL ?= http://mysite.docker.amazee.io/

PHP_LINT_EXTENSIONS ?= php
PHP_LINT_TARGETS ?= .
PHP_LINT_TARGETS := $(subst $\",,$(PHP_LINT_TARGETS))

# Prefix of the Docker images.
DOCKER_IMAGE_PREFIX ?= amazeeio

# Width of the target column in help target.
HELP_TARGET_WIDTH = 20

# Print verbose messages.
VERBOSE ?= 1

# Colors for output text.
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
RESET  := $(shell tput -Txterm sgr0)

#-------------------------------------------------------------------------------
# FUNCTIONS.
#-------------------------------------------------------------------------------

##
# Execute command and display executed command to user.
#
define exec
	@printf "$$ ${YELLOW}${subst ",',${1}}${RESET}\n" && $1
endef

##
# Display the target title to user.
#
define title
	$(if $(VERBOSE),@printf "${GREEN}==> ${1}...${RESET}\n")
endef

# Pass arguments from CLI to commands.
# @see https://stackoverflow.com/a/6273809/1826109
%:
	@:
