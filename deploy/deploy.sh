#!/usr/bin/env bash
#
# Deploy-ul Wishio pe server. Îl cheamă scriptul de deploy din Forge sau Ploi,
# după `git pull` și înainte de reîncărcarea PHP-FPM (docs/25 § 4).
#
# Repo-ul e un monorepo: aplicația Laravel stă în backend/, iar panoul servește
# backend/public. `.env` rămâne în rădăcina site-ului, unde îl editează panoul.
#
#   PHP_BIN       binarul PHP al site-ului (implicit: php)
#   COMPOSER_BIN  composer (implicit: composer)

set -euo pipefail

cd "$(dirname "$0")/../backend"

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

# Laravel caută .env în backend/; panoul îl ține în rădăcina site-ului.
if [ ! -e .env ]; then
    ln -s ../.env .env
fi

"$COMPOSER_BIN" install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# CSS-ul și JS-ul paginilor publice: landing, linkul personal, documentele legale.
if [ -f package-lock.json ]; then
    npm ci --no-audit --no-fund
else
    npm install --no-audit --no-fund
fi
npm run build

# Migrările trebuie să meargă și cu versiunile vechi ale aplicației (docs/10 § 4).
"$PHP_BIN" artisan migrate --force

"$PHP_BIN" artisan optimize

# Worker-ul termină jobul curent și repornește cu codul nou.
"$PHP_BIN" artisan queue:restart

# Aplicația pornește cu noul cod.
APP_URL="$(grep -E '^APP_URL=' .env | cut -d= -f2- | tr -d '"')"
curl --fail --silent --show-error --max-time 20 "$APP_URL/up" > /dev/null

echo "Deploy gata: $(git rev-parse --short HEAD)"
