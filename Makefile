.PHONY: help

# detect interactive / non interactive shells
INTERACTIVE:=$(shell [ -t 0 ] && echo 1)

ifdef INTERACTIVE
# is a terminal
	TTY_DOCKER=-it
	TTY_COMPOSE=
else
# bash job
	TTY_DOCKER=
	TTY_COMPOSE=-T
endif

## Display this help text
help:
	$(info ---------------------------------------------------------------------)
	$(info -                        Available targets                          -)
	$(info ---------------------------------------------------------------------)
	@awk '/^[a-zA-Z\-\_0-9]+:/ {                                   \
	nb = sub( /^## /, "", helpMsg );                             \
	if(nb == 0) {                                                \
		helpMsg = $$0;                                             \
		nb = sub( /^[^:]*:.* ## /, "", helpMsg );                  \
	}                                                            \
	if (nb)                                                      \
		printf "\033[1;31m%-" width "s\033[0m %s\n", $$1, helpMsg; \
	}                                                              \
	{ helpMsg = $$0 }'                                             \
	$(MAKEFILE_LIST) | column -ts:

.env:
	cp .env.dist .env

autoconf: .env

## Pull latest images
pull: autoconf
	docker-compose pull --no-parallel
	docker pull registry.idx.ovh/node:14.11-buster

## Start the services
up: autoconf
	docker-compose up -d

## Alias -> up
start: up

## Stop all services
stop:
	docker-compose stop

## Stop, then... start
restart: stop start

## Down all the containers
down:
	docker-compose down --remove-orphans --volumes

## Logs for all containers of the project
logs:
	docker-compose logs -tf --tail=1000

## Status of containers
ps:
	docker-compose ps

## Enter php service (bash)
php:
	docker-compose exec -u www-data php bash

## Enter php service (bash with root)
php-root:
	docker-compose exec -u root php bash

## Build .phar
build-phar:
	docker-compose exec -e APP_ENV=prod -u www-data php composer install --no-dev
	docker-compose exec -e APP_ENV=prod -u www-data php composer dump-env prod
	docker-compose exec -e APP_ENV=prod -u www-data php ./bin/console cache:clear
	docker-compose exec -e APP_ENV=prod -u www-data php ./bin/console cache:warmup
	docker run --rm ${TTY_DOCKER} --volume="$$(pwd):/app:delegated" ajardin/humbug-box compile -vvv
	rm .env.local.php

## Build docker image
build-docker:
	docker image build -t kilik/backup .

## Build binary, docker image and push it
build: build-phar build-docker
	docker image push kilik/backup
