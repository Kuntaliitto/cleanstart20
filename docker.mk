include .env
-include .env.local
export $(shell sed 's/=.*//' .env.local)

.PHONY: up down stop prune ps shell drush logs nuke rm-containers rm-field rm-vendor-dir rm-clean backup

default: help

DRUPAL_ROOT ?= /var/www/html/docroot

help:
	@echo "Help"
	@echo ""
	@echo "Commands available, eg. make up"
	@echo ""
	@echo "help"
	@echo "        Show this help message"
	@echo "backup"
	@echo "        Back up the database"
	@echo "clean"
	@echo "        Same as rm-clean. Remove vendor directory, docroot/core and stuff"
	@echo "down"
	@echo "        Same as stop. Stop the containers"
	@echo "drush"
	@echo "        Run a Drush command"
	@echo "logs"
	@echo "        Print out logs"
	@echo "nuke"
	@echo "        Remove containers and vendor directory"
	@echo "prune"
	@echo "        Remove containers"
	@echo "ps"
	@echo "        List containers"
	@echo "rm-clean"
	@echo "        Same as clean. Remove vendor directory, docroot/core and stuff"
	@echo "rm-containers"
	@echo "        Remove all containers"
	@echo "rm-field field=FIELDNAME"
	@echo "        Remove field FIELDNAME from database config"
	@echo "rm-vendor-dir"
	@echo "        Remove vendor directory"
	@echo "shell"
	@echo "        Open a shell in the container"
	@echo "start"
	@echo "        Same as up. Start the containers"
	@echo "stop"
	@echo "        Same as down. Stop the containers"
	@echo "up"
	@echo "        Same as start. Start the containers"

up:
	@echo "Starting up containers for $(PROJECT_NAME)..."
	docker-compose pull
	docker-compose up -d --remove-orphans

down: stop

status: ps
start: up

backup:
	@echo "Creating backup"
	docker exec $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") drush -r $(DRUPAL_ROOT) sql-dump --gzip --result-file auto $(filter-out $@,$(MAKECMDGOALS))

clean: rm-clean

drush:
	@docker exec -w /var/www/html/drush $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") drush -r $(DRUPAL_ROOT) $(filter-out $@,$(MAKECMDGOALS))

logs:
	@docker-compose logs -f $(filter-out $@,$(MAKECMDGOALS))

nuke: rm-containers rm-vendor-dir

prune:
	@echo "Removing containers for $(PROJECT_NAME)..."
	@docker-compose down -v

ps:
	@docker ps --filter name='$(PROJECT_NAME)*'

shell:
	docker exec -ti -e COLUMNS=$(shell tput cols) -e LINES=$(shell tput lines) $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") bash

stop:
	@echo "Stopping containers for $(PROJECT_NAME)..."
	@docker-compose stop

rm-clean:
	@echo "Removing vendor directory, docroot/core and stuff for $(PROJECT_NAME)..."
	rm -rf vendor docroot/core

rm-containers:
	@echo "Removing containers for $(PROJECT_NAME)..."
	@docker container ls | awk '{ print $$1,$$NF }' | grep $(PROJECT_NAME) | awk '{print $$1 }' | xargs -I {} docker container rm -f {}

# To fix:
#  [error]  Unable to determine class for field type 'metatag' found in the 'field.storage.node.field_ref_subject_meta' configuration
# Example: make rm-field field=field.storage.node.field_ref_subject_meta
# Re: https://drupal.stackexchange.com/a/246231/27135
rm-field:
	$(MAKE) backup
	@echo "Removing field from database config for $(PROJECT_NAME)..."
	@echo $(field)
	docker exec $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") drush -r $(DRUPAL_ROOT) sqlq "DELETE FROM cache_config"
	docker exec $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") drush -r $(DRUPAL_ROOT) sqlq "DELETE FROM config          WHERE name = '$(field)' OR data LIKE '%$(field)%'"
	docker exec $(shell docker ps --filter name='$(PROJECT_NAME)_php' --format "{{ .ID }}") drush -r $(DRUPAL_ROOT) sqlq "DELETE FROM config_snapshot WHERE name = '$(field)' OR data LIKE '%$(field)%'"

rm-vendor-dir:
	@echo "Removing vendor directory for $(PROJECT_NAME)..."
	rm -rf vendor

# https://stackoverflow.com/a/6273809/1826109
%:
	@:
