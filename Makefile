.PHONY: setup test lint build dev fresh
setup:
	composer install
	npm ci
	php artisan key:generate
	php artisan storage:link
	php artisan migrate --seed
	npm run build

test:
	php artisan test --compact

lint:
	vendor/bin/pint --format agent
	composer types:check
	npm run lint:check
	npm run format:check
	npm run types:check

build:
	npm run build

dev:
	composer run dev

fresh:
	@test "$${APP_ENV}" = "local" || (echo "fresh is allowed only in local" && exit 1)
	php artisan migrate:fresh --seed
