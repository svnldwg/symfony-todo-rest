#CONFIGURATION
export PROJECT_IP = 127.0.0.1
export PROJECT_NAME = symfony-todo-rest
export PROJECT_DOMAIN = ${PROJECT_NAME}.dev.local
export PROJECT_NAME_UNDERSCORE = $(subst  -,_,${PROJECT_NAME})
export SHELL = bash

export POSTGRES_HOST = ${PROJECT_NAME}-database
export POSTGRES_PASSWORD = pass
export POSTGRES_USER = ${PROJECT_NAME_UNDERSCORE}
export POSTGRES_DB = ${PROJECT_NAME_UNDERSCORE}

#xdebug
export HOST_PORT = 9000

#DOCKER COMPOSE SETTINGS
export PHP_IDE_CONFIG = serverName=${PROJECT_DOMAIN}

export ENVIRONMENT=dev

#DOCKER
DOCKER = docker
DOCKER_COMPOSE = docker-compose -f ../docker/docker-compose.yml
CLI = $(DOCKER_COMPOSE) exec -T php
CLI_COMPOSER = $(DOCKER_COMPOSE) exec -T php php -d memory_limit=-1 /usr/bin/composer
CONSOLE = $(CLI) ./vendor/bin/console

start: ##@development start project
	$(DOCKER_COMPOSE) up -d
.PHONY: start

stop: ##@development stop project
	$(DOCKER_COMPOSE) stop
.PHONY: stop

down: ##@development delete project container
	$(DOCKER_COMPOSE) down
.PHONY: down

build:
	$(DOCKER_COMPOSE) build
.PHONY: build

ps: ##@development show running container
	$(DOCKER_COMPOSE) ps
.PHONY: ps

logs: ##@development show server logs
	$(DOCKER_COMPOSE) logs -f
.PHONY: logs

cli: ##@development get shell
	$(DOCKER_COMPOSE) exec php $(SHELL)
.PHONY: cli

composer-install: ##@development run composer install
	$(CLI_COMPOSER) install --ansi
.PHONY: composer-install

composer-update: ##@development run composer update
	$(CLI_COMPOSER) update
.PHONY: composer-update

composer-dump-autoload: ##@development run composer dump-autoload
	$(CLI_COMPOSER) dump-autoload -a
.PHONY: composer-dump-autoload

setup: start composer-install doctrine-migrate ##@development configure development environment
	grep -q -F "${PROJECT_IP} ${PROJECT_DOMAIN}" /etc/hosts || sudo sh -c "echo '${PROJECT_IP} ${PROJECT_DOMAIN}' >> /etc/hosts"
	$(DOCKER_COMPOSE) restart php
.PHONY: setup

phpstan: ##@development run phpstan
	$(CLI) ./vendor/bin/phpstan analyze -c dev/phpstan.neon
.PHONY: phpstan

php-cs-fixer: ##@development run php-cs-fixer
	$(CLI) ./vendor/bin/php-cs-fixer fix --config=dev/csfixer.php_cs
.PHONY: php-cs-fixer

phpunit: ##@development run phpunit
	$(CLI) ./vendor/bin/phpunit
.PHONY: phpunit

codecheck: php-cs-fixer phpstan phpunit
.PHONY: codecheck

symfony-autowiring:
	$(CLI) php bin/console debug:autowiring
.PHONY: symfony-autowiring

symfony-container:
	$(CLI) php bin/console debug:container
.PHONY: symfony-container

make-entity:
	$(CLI) php bin/console make:entity
.PHONY: make-entity

make-migration:
	$(CLI) php bin/console make:migration
.PHONY: make-migration

doctrine-migrate:
	$(CLI) php bin/console doctrine:migrations:migrate --no-interaction
.PHONY: doctrine-migrate

clear-cache:
	$(CLI) php bin/console cache:clear