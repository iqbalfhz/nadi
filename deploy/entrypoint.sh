#!/bin/sh
set -e

# Only the "app" (web) service sets RUN_SETUP=true in docker-compose.yml —
# the reverb/scheduler services share this same image but skip this block,
# so migrations and cache-rebuilding don't run redundantly (or race) across
# three containers starting at once.
if [ "$RUN_SETUP" = "true" ]; then
    php artisan package:discover --ansi
    php artisan filament:upgrade
    php artisan migrate --force
    php artisan storage:link || true
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
fi

exec "$@"
