#!/bin/sh
set -e

# Only run the bootstrap routine when starting the web server or a console command.
if [ "$1" = 'frankenphp' ] || [ "$1" = 'php' ] || [ "$1" = 'bin/console' ]; then
    echo "Waiting for the database to become available..."
    ATTEMPTS=0
    until php bin/console dbal:run-sql "SELECT 1" >/dev/null 2>&1; do
        ATTEMPTS=$((ATTEMPTS + 1))
        if [ "$ATTEMPTS" -ge 60 ]; then
            echo "Database did not become available in time." >&2
            exit 1
        fi
        sleep 1
    done

    echo "Database is ready. Applying migrations..."
    php bin/console doctrine:migrations:migrate --no-interaction --all-or-nothing --allow-no-migration
fi

# Hand over to the base image entrypoint (sets up PHP, then runs the command).
exec docker-php-entrypoint "$@"
