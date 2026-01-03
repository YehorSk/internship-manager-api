#!/usr/bin/env bash
set -e

APP_PATH="${APP_PATH}"

if [ ! -f "$APP_PATH/.env" ]; then
    if [ -f "$APP_PATH/.env.example" ]; then
        cp "$APP_PATH/.env.example" "$APP_PATH/.env"
        chown ${UID}:${GID} "$APP_PATH/.env"
    fi
fi

chown -R ${UID}:${GID} "$APP_PATH"
chmod -R 775 "$APP_PATH/storage" "$APP_PATH/bootstrap/cache"

if [ ! -d "$APP_PATH/vendor" ]; then
    gosu ${UID}:${GID} composer install --working-dir="$APP_PATH"
fi

exec php-fpm -F
