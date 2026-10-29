#!/bin/bash
set -euo pipefail

ENV_FILE="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/.env"

if [[ -f "$ENV_FILE" ]]; then
    echo "Docker environment already exists at $ENV_FILE"
    exit 0
fi

if ! command -v openssl >/dev/null 2>&1; then
    echo "openssl is required to generate Docker signing keys." >&2
    exit 1
fi

umask 077
{
    for key in \
        WORDPRESS_AUTH_KEY \
        WORDPRESS_SECURE_AUTH_KEY \
        WORDPRESS_LOGGED_IN_KEY \
        WORDPRESS_NONCE_KEY \
        WORDPRESS_AUTH_SALT \
        WORDPRESS_SECURE_AUTH_SALT \
        WORDPRESS_LOGGED_IN_SALT \
        WORDPRESS_NONCE_SALT \
        WORDPRESS_CACHE_KEY_SALT
    do
        printf '%s=%s\n' "$key" "$(openssl rand -base64 48)"
    done
} > "$ENV_FILE"

echo "Created Docker-only signing keys in $ENV_FILE"
echo "Keep this ignored file private; regenerating it invalidates Docker sessions."
