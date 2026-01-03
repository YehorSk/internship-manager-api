#!/usr/bin/env bash
set -e

APP_PATH="${APP_PATH}"

if [ ! -f "$APP_PATH/.env" ]; then
    if [ -f "$APP_PATH/.env.example" ]; then
        cp "$APP_PATH/.env.example" "$APP_PATH/.env"
    fi
fi

chown -R ${UID}:${GID} "$APP_PATH"
cd "$APP_PATH"

if [ ! -d "$APP_PATH/node_modules" ]; then
    gosu ${UID}:${GID} npm install
fi

gosu ${UID}:${GID} npm run dev