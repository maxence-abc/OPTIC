#Environnement variables docker
ENV_FILE ?= .env.docker

# Executables (local)
DOCKER_COMP = docker compose

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
SYMFONY  = $(PHP) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up start down logs sh composer vendor sf cc test

## —— 🎵 🐳 The Symfony Docker Makefile 🐳 🎵 ——————————————————————————————————
help: ## Outputs this help screen
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

check-env: ## Vérifie que le fichier $(ENV_FILE) existe
	@if [ ! -f $(ENV_FILE) ]; then \
		echo "❌ Fichier $(ENV_FILE) introuvable !"; \
		exit 1; \
	fi

## —— Docker 🐳 ————————————————————————————————————————————————————————————————

build: check-env ## Builds Docker avec .env.docker
	docker compose --env-file $(ENV_FILE) build --pull --no-cache

start: ## Start the docker hub en detached mode avec .env.docker
	docker compose --env-file $(ENV_FILE) up --detach

stop: ## Stop the docker hub avec .env.docker
	docker compose --env-file $(ENV_FILE) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Connect to the FrankenPHP container
	@$(PHP_CONT) sh

bash: ## Connect to the FrankenPHP container via bash so up and down arrows go to previous commands
	@$(PHP_CONT) bash

test: ## Start tests with phpunit, pass the parameter "c=" to add options to phpunit, example: make test c="--group e2e --stop-on-failure"
	@$(eval c ?=)
	@$(DOCKER_COMP) exec -e APP_ENV=test php bin/phpunit $(c)

update: ## Update the project
	@$(DOCKER_COMP) exec php composer update

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, pass the parameter "c=" to run a given command, example: make composer c='req symfony/orm-pack'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

vendor: ## Install vendors according to the current composer.lock file
vendor: c=install --prefer-dist --no-dev --no-progress --no-scripts --no-interaction
vendor: composer

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
sf: ## List all Symfony commands or pass the parameter "c=" to run a given command, example: make sf c=about
	@$(eval c ?=)
	@$(SYMFONY) $(c)

cc: c=c:c ## Clear the cache
cc: sf

deploy: ## Déploiement complet du projet
	@echo "📦 Installation des dépendances PHP..."
	@$(COMPOSER) install --no-interaction --prefer-dist --no-progress
	@echo "🧱 Migration de la base de données..."
	@$(SYMFONY) doctrine:migrations:migrate --no-interaction
	@echo "🧼 Nettoyage du cache Symfony..."
	@$(SYMFONY) cache:clear
	@echo "📦 Installation des dépendances Node.js..."
	@$(DOCKER_COMP) exec php npm install
	@echo "🛠️  Compilation des assets..."
	@$(DOCKER_COMP) exec php npm run build
	@echo "✅ Déploiement terminé avec succès."