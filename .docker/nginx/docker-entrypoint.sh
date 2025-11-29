#!/bin/bash
set -e

CERT_DIR=/etc/nginx
CRT_FILE=$CERT_DIR/cert.pem
KEY_FILE=$CERT_DIR/key.pem

if [ ! -f "$CRT_FILE" ] || [ ! -f "$KEY_FILE" ]; then
  mkdir -p $CERT_DIR
  openssl req -x509 -nodes -newkey rsa:2048 \
    -keyout $KEY_FILE \
    -out $CRT_FILE \
    -subj "/CN=localhost" \
    -days 3650
fi

exec nginx -g 'daemon off;'
