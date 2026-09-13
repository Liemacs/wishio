# Wishio — comenzi de dezvoltare
# Cache npm alternativ: cache-ul global are fisiere root-owned.
# Fix permanent: sudo chown -R $(id -u):$(id -g) ~/.npm
export npm_config_cache := $(HOME)/.cache/npm-wishio

.DEFAULT_GOAL := help
.PHONY: help up down setup api mobile queue test lint fresh schedule

help: ## Afiseaza comenzile disponibile
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2}'

up: ## Porneste MySQL, Redis, Mailpit
	docker compose -f docker/compose.yaml up -d

down: ## Opreste serviciile
	docker compose -f docker/compose.yaml down

setup: ## Instalare completa (prima rulare)
	cd backend && composer install && cp -n .env.example .env || true
	cd backend && php artisan key:generate
	cd backend && php artisan migrate --seed
	cd mobile && npm install

api: ## Porneste API-ul Laravel, accesibil si de pe telefon din reteaua locala (port 8000)
	cd backend && php artisan serve --host=0.0.0.0 --port=8000

schedule: ## Porneste programarea: remindere, notificari, rezumatul saptamanal
	cd backend && php artisan schedule:work

queue: ## Porneste worker-ul de cozi
	cd backend && php artisan queue:listen --tries=1

mobile: ## Porneste Expo
	cd mobile && npx expo start

test: ## Ruleaza testele
	cd backend && php artisan test
	cd mobile && npx tsc --noEmit

lint: ## Formatare si verificari
	cd backend && ./vendor/bin/pint
	cd mobile && npx tsc --noEmit

fresh: ## Reconstruieste baza de date cu seed-uri
	cd backend && php artisan migrate:fresh --seed
