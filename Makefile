# Detect if running inside the container (LARAVEL_SAIL=1) or on the host
ifdef LARAVEL_SAIL
    ARTISAN = php artisan
    PINT = ./vendor/bin/pint
else
    ARTISAN = ./vendor/bin/sail artisan
    PINT = ./vendor/bin/sail bin pint
endif

.PHONY: up down test lint fix fresh api-docs shell init reload post

init:
	cp -n .env.example .env 2>/dev/null; true
	$(ARTISAN) key:generate --no-interaction
	$(ARTISAN) migrate --force
	$(ARTISAN) storage:link 2>/dev/null; true

up:
ifndef LARAVEL_SAIL
	./vendor/bin/sail up -d
else
	@echo "Already running inside the container."
endif

down:
ifndef LARAVEL_SAIL
	./vendor/bin/sail down
else
	@echo "Cannot stop containers from inside the container."
endif

test:
	$(ARTISAN) test --parallel

lint:
	$(PINT) --test

fix:
	$(PINT)

fresh:
	$(ARTISAN) migrate:fresh --seed

api-docs:
	$(ARTISAN) scramble:export

shell:
ifndef LARAVEL_SAIL
	./vendor/bin/sail shell
else
	@echo "Already inside the container."
endif

reload:
ifdef LARAVEL_SAIL
	php artisan octane:reload
else
	docker compose exec laravel.test php artisan octane:reload
endif

post: fix reload
