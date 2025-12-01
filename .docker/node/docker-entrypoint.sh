#!/usr/bin/env bash
set -e
for dir in /var/www/*; do
  chown -R 1000:1000 "$dir"
done
cd /var/www/internship-manager-web
npm install
npm run dev
